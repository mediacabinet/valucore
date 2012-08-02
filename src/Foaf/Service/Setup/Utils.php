<?php
namespace Foaf\Service\Setup;

use Foaf\Version\SoftwareVersion,
	Foaf\Service\Broker,
	Foaf\Service\Setup\Exception;

/**
 * Setup utilities
 * 
 * @author Juha Suni
 *
 */
class Utils{
    
    /**
     * Service broker instance
     *
     * @var \Foaf\Service\Broker
     */
    protected $serviceBroker = null;
    
    /**
     * Options
     * 
     * @var Utils\Options
     */
    protected $options;
    
    public function __construct(Broker $broker, $config = null)
    {
        $this->setServiceBroker($broker);
        
        if($config !== null){
            $this->setConfig($config);
        }
    }
    
    /**
     * Check whether a module directory exists in one of the
     * module locations
     * 
     * @param string $module
     * @throws \Exception
     * @return boolean
     */
    public function moduleExists($module)
    {
        return $this->locateModule($module) !== false;
    }
    
    /**
     * Locate module file (phar) or directory
     * 
     * @param string $module
     * @return string|boolean
     */
	public function locateModule($module)
	{
	    $dirs = $this->getOption('module_dirs');
	     
	    foreach($dirs as $dir){
	        
	        $file = $dir . DIRECTORY_SEPARATOR . $module;
	        
	    	if(file_exists($file)){
	    		return $file;
	    	}
	    }
	    
	    return false;
	}
	
	/**
	 * Detect the name of the module for file system path
	 * 
	 * @param string $path Path in local file system
	 * @return string|null Module name
	 */
	public function whichModule($path){
	    $dirs = $this->getOption('module_dirs');
	    
	    foreach($dirs as $dir){
	        if(strpos($path, $dir) === 0){
	            $dir = substr($path, strlen($dir));
	            $dir = ltrim($dir, DIRECTORY_SEPARATOR);
	            
	            $a = explode(DIRECTORY_SEPARATOR, $dir);
	            return $a[0];
	        }
	    }
	    
	    return null;
	}
    
	/**
	 * Get current version for module
	 * 
	 * @param string pathule
	 * @return string|null
	 */
    public function getModuleVersion($module)
    {
        $path = $this->locateModule($module);
        
        if($path){
            
            $definition = $path . DIRECTORY_SEPARATOR . $this->getOption('definition_file');
            
            /**
             * Read version from definition file (e.g. definition.ini)
             * from either module directory or PHAR archive
             */
            if(is_dir($path) && file_exists($definition)){
            	$config = \Zend\Config\Factory::fromFile($definition);
            	return $config['version'];
            }
            else if(is_file($path) && file_exists('phar://'.$definition)){
                $config = \Zend\Config\Factory::fromFile('phar://'.$definition);
                return $config['version'];
            }
            else{
                return null;
            }
        }
        else{
            return null;
        }
    }
    
    /**
     * Install module
     *
     * @param string $module	Module name
     * @param string $version 	Version to install, if omitted,
     * 							latest version is installed
     * @param array	$options	Installer options
     * @return boolean			True on success
     */
    public function install($module, $version, array $options = null){
        return $this->downloadAndInstall($module, $version, $options, true);
    }
    
    /**
     * Setup module
     *
     * @param string $module	Module name
     * @param string $version 	Version to install, if omitted,
     * 							latest version is installed
     * @param array	$options	Installer options
     * @return boolean			True on success
     */
    public function setup($module, $version, array $options = null){
        return $this->downloadAndInstall($module, $version, $options, false);
    }
    
    public function upgrade($module, $version, array $options = null){
        
        /**
         * Fetch previous version for module
         */
        $oldVersion = $this->getModuleVersion($module);
        $oldVersion = new SoftwareVersion($oldVersion);
        
        if($oldVersion->isLt($version)){
            throw new \Exception('Unable to upgrade to '.$version.' (version '.$oldVersion.' is already installed)');
        }
        
        return $this->install($module, $version, $options);
    }
    
    /**
     * Uninstall module
     * 
     * @param string $module	Module name
     * @return boolean 			True when module was removed, false if
     * 							nothing was removed
     */
    public function uninstall($module){
        if($this->moduleExists($module)){
            return $this->removeModuleFiles($module);
        }
    }
    
    /**
     * Load (install) module files
     *
     * @param string $module	Module name
     * @param string $version 	Version to install, if omitted,
     * 							latest version is installed
     * @return boolean			True on success
     */
    public function loadModule($module, $version)
    {
    	$dirs	= $this->getOption('module_dirs');
    	$repos	= $this->getRepositoryQueue();
    	$dir	= array_shift(array_values($dirs));
    
    	while($repos->valid()){
    
    		$url = $repos->current();
    
    		if($this->downloadArchive($url, $module, $version, $dir)){
    			return true;
    		}
    
    		$repos->next();
    	}
    
    	return false;
    }
    
    /**
     * Resolve module dependencies, recursively
     * 
     * This method returns an array with dependent module names
     * as keys and versions as values. There may also be a 
     * dependency back to module itself with greater version
     * number.
     * 
     * Example of dependency tree:
     * - A 1.0
     *   - C 1.0
     *     - A 1.1
     *   - D 2.0
     *     - E 1.0
     *     - B 1.0
     *   - B 1.1
     *   
     * Resolved dependencies:
     * A : 1.1
     * C : 1.0
     * E : 1.0
     * B : 1.1
     * D : 2.0
     * 
     * @param string $module
     * @throws \Exception
     * @return ArrayObject
     */
    public function resolveDependencies($module){
        
        $version = $this->getModuleVersion($module);
        
        if(null === $version){
            throw new \Exception(sprintf(
                "Unable to resolve dependencies for %s. Module definitions are missing or incomplete.", 
                $module
            ));
        }
        
        $resolved = new \ArrayObject();
        $this->resolveDepsRecursive($module, $version, $resolved);
        
        // Remove dependency to module itself if resolved version 
        // is less than or equal to current version.
        if($resolved->offsetExists($module) && SoftwareVersion::compare($resolved[$module], $version) <= 0){
            unset($resolved[$module]);
        }
        
        return $resolved;
    }
    
    /**
     * Resolve dependencies recursively. The level and order in module dependency
     * hierarchy defines the module's priority.
     * 
     * @param string $module
     * @param string $version
     * @param \ArrayObject $resolved
     */
    protected function resolveDepsRecursive($module, $version, \ArrayObject $resolved){
    
        // ask for deps, if setup available
        if($this->hasSetupService($module)){
            
            $deps = $this->initSetupService($module)->exec(
                'getDependencies'
            )->first();
        
            foreach ($deps as $name => $specs){
        
                // make sure the same module doesn't get resolved again
                if(!$resolved->offsetExists($name)){
                    $this->resolveDepsRecursive($name, $specs['version'], $resolved);
                }
            }
        }
    
        // replace if exists with lower version number
        if($resolved->offsetExists($module)){
            $new = new SoftwareVersion($version);
    
            // remember the greatest versions
            if($new->isGt($resolved[$module])){
                $resolved[$module] = $version;
            }
        }
        // append if doesn't exist
        else{
            $resolved[$module] = $version;
        }
    
        return $resolved;
    }
    
    /**
     * Download module and execute either installer or setup
     * 
     * @param string $module
     * @param string $version
     * @param array $options
     * @param boolean $fullInstall Set true to execute module installer
     * @return boolean
     */
    public function downloadAndInstall($module, $version, array $options = null, $fullInstall = true){
    
        $loaded = false;
    
        /**
         * Fetch previous version for module
         */
        $oldVersion = $this->getModuleVersion($module);
         
        /**
         * Test if given or greater version is already installed
         */
        if($oldVersion){
            $oldVersion = new SoftwareVersion($oldVersion);
            	
            if($oldVersion->isGte($version)){
                $loaded = true;
            }
        }
         
        /**
         * Load module files if necessary
         */
        if(!$loaded){
            $this->loadModule($module, $version);
        }
         
        /**
         * Execute module specific setup
         */
        if($this->hasSetupService($module)){
            	
            $setupArgs = array(
                    'options' => $options
            );
            	
            $setup = $this->initSetupService($module);
            	
            // complete existing installation
            if($loaded){
                $setup->exec(
                    $fullInstall ? 'install' : 'setup',
                    $setupArgs
                );
            }
            // install as new
            elseif($oldVersion === null){
                $setup->fork(
                    $fullInstall ? 'install' : 'setup',
                    $setupArgs
                );
            }
            // upgrade
            else{
                $setup->fork(
                    'upgrade',
                    array('from' => $oldVersion, 'options' => $options)
                );
            }
        }
         
        return true;
    }
    
    /**
     * Download archive file from URL and install to local 
     * directory
     * 
     * @param string $url
     * @param string $module
     * @param string $version
     * @param string $destination
     */
    protected function downloadArchive($url, $module, $version, $destination){
        
        $uri = new \Zend\Uri\Http($url);
        $uri->setQuery(array('module' => $module, 'version' => $version));
        
        $client = new \Zend\Http\Client($uri);
        $client->setAdapter('Zend\Http\Client\Adapter\Curl');
        $client->send();
        
        $response = $client->getResponse();
        
        if($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_200){
            
            $path = $destination . DIRECTORY_SEPARATOR . $module;
            $file = $path . '.phar';
            
            /**
             * Write response to a temporary file
             */
            $tmpFile = tempnam(sys_get_temp_dir(), 'zfse-module-');
            file_put_contents($tmpFile, $response->getBody());
            
            /**
             * Construct a new Phar instance from the temporary file
             */
            $phar = new \Phar($tmpFile);
            
            /**
             * Remove existing module files
             */
            $this->removeModuleFiles($module);
            
            // extract and remove temporary file
            if($this->getOption('extract_phars')){
                
                if(!is_writable(dirname($path))){
                	throw new Exception\ModuleFolderNotWritableException(
                		'Unable to copy module files to path '.$path
                	);
                }
                
            	$phar->extractTo($path);
            	unlink($tmpFile);
            }
            // move temporary phar file in place
            else{
                if(!is_writable(dirname($file))){
                	throw new Exception\ModuleFolderNotWritableException(
                		'Unable to copy PHAR archive to path '.$path
                	);
                }
                
                rename($tmpFile, $file);
            }

        	return true;
        }
        else{
            return false;
        }
    }
    
    /**
     * Remove module files
     * 
     * Removes both PHAR archives and directories with
     * the module name within one of the module directories.
     * 
     * @param string $module
     * @throws Exception\ModuleFolderNotWritableException
     */
    protected function removeModuleFiles($module){
    	$path = $this->locateModule($module);

    	/**
    	 * Test if a directory exists and remove
    	 * recursively
    	 */
    	if($path && is_dir($path)){
    	    
    	    if(!is_writable($path)){
    	        throw new Exception\ModuleFolderNotWritableException(
    	        	'Unable to remove module from path '.$path
    	        );
    	    }
    	    
    	    $iterator = new \RecursiveIteratorIterator(
    	    	new \RecursiveDirectoryIterator($path),
    	    	\RecursiveIteratorIterator::CHILD_FIRST
    	    );
    	    
    	    foreach ($iterator as $path){
    	    	if ($path->isDir()) {
    	    		rmdir($path->__toString());
    	    	}
    	    	else{
    	    		unlink($path->__toString());
    	    	}
    	    }
    	    
    	    rmdir($path);
    	    
    	    return true;
    	}
    	
    	/**
    	 * Test if a phar file exists and remove
    	 */
    	$file = $this->locateModule($module);
    	if(file_exists($file)){
    	    
    	    if(!is_writable($file)){
    	    	throw new Exception\ModuleFolderNotWritableException(
    	    		'Unable to remove module from path '.$path
    	    	);
    	    }
    	        	    
    	    unlink($file);
    	    return true;
    	}
    	
    	return false;
    }
    
    protected function getRepositoryQueue(){
        $repos = $this->getOption('repositories');
        
        $queue = new \SplPriorityQueue();
        foreach($repos as $specs){
            $queue->insert($specs['url'], $specs['priority']);
        }
        
        return $queue;
    }
    
    public function initSetupService($module){
        
        $moduleSetup = $module . 'Setup';  
        
        return $this->getServiceBroker()->service($moduleSetup);
    }
    
    public function hasSetupService($module){
        $moduleSetup = $module . 'Setup';
        return $this->getServiceBroker()->exists($moduleSetup);
    }
    
    /**
     * Retrieve service broker
     *
     * @return \Foaf\Service\Broker
     */
    public function getServiceBroker()
    {
    	return $this->serviceBroker;
    }
    
    /**
     * Set service broker
     *
     * @param \Foaf\Service\Broker $broker
     * @return \Foaf\Service\Setup\Utils
     */
    public function setServiceBroker(Broker $broker)
    {
    	$this->serviceBroker = $broker;
    	return $this;
    }
    
    /**
     * Set service options
     *
     * @param  array|Traversable|string $config
     * @return Service
     */
    public function setConfig($config)
    {
        
        if(is_string($config) && file_exists($config)){
            $options = \Zend\Config\Factory::fromFile($config);
            $this->setOptions($options);
        }
        elseif(!is_array($config) && !($config instanceof \Traversable)){
            throw new \InvalidArgumentException('Invalid parameter $config or config file not found');
        }
        
    	$this->options = new Utils\Options($config);
    	return $this;
    }
    
    /**
     * Retrieve service options
     *
     * @return array
     */
    public function getOptions()
    {
    	if(!$this->options){
    		$this->options = new Utils\Options();
    	}
    
    	return $this->options;
    }
    
    /**
     * Is an option present?
     *
     * @param  string $key
     * @return bool
     */
    public function hasOption($key)
    {
    	return $this->getOptions()->__isset($key);
    }
    
    /**
     * Set option
     *
     * @param string $key
     * @param mixed $value
     * @return Service
     */
    public function setOption($key, $value)
    {
    	$this->getOptions()->__set($key, $value);
    	return $this;
    }
    
    /**
     * Retrieve a single option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
    	return $this->getOptions()->__get($key);
    }
}