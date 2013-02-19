<?php
namespace Valu\Service\Setup;

use Valu\Service\Exception\MissingParameterException;
use Valu\Service\AbstractService;
use Valu\Service\Setup\Utils;
use Valu\Service\Broker;
use Valu\Version\SoftwareVersion;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class AbstractSetup 
    extends AbstractService
    implements ServiceLocatorAwareInterface
{
    
    protected $optionsClass = 'Valu\Service\Setup\SetupOptions';
    
    /**
     * Service broker instance
     *
     * @var \Valu\Service\Broker
     */
    protected $serviceBroker = null;
    
    /**
     * Setup utils
     */
    protected $utils;
    
    /**
     * Retrieve array of immediate dependencies
     * 
     * @return array
     */
    public function getDependencies(){
        return $this->getOption('dependencies');
    }
    
    /**
     * Retrieve module name for setup service
     * 
     * @return string
     */
    public abstract function getName();
    
    /**
     * Retrieve version for module
     * 
     * @return string
     */
    public function getVersion(){
        return $this->utils()->getModuleVersion($this->getName());
    }
    
    /**
     * Install module dependencies and execute setup when
     * ready 
     * 
     * @param array $options Setup options
     */
    public function install($version = null, array $options = array())
    {
        $module = $this->utils()->whichModule($this);
        if ($version === null) {
            $version = $this->utils()->getModuleVersion($module);
            
            if (!$version) {
                throw new MissingParameterException('Parameter version is missing and cannot be autodetected');
            }
        }
        
        return $this->utils()->install($module, $version);
    }
    
    /**
     * Setup module
     * 
     * @param array $options
     */
    public abstract function setup(array $options = array());
    
    /**
     * Upgrade module from previous version
     * 
     * This method should be invoked only after the new
     * version has been loaded. This method should also ignore
     * any values of $from that indicate version number that
     * is greater than or equal to current version.
     * 
     * This method should ensure backwards compatibility and
     * prepare data from previous version for the current
     * version.
     * 
     * @param string $from Version information
     * @param array $options
     */
	public function upgrade($from, array $options = array()){
	    
	    $to = $this->utils()->getModuleVersion($this->getName());
	    
	    if(SoftwareVersion::compare($to, $from) <= 0){
	        throw new Exception\IllegalVersionException(
                sprintf('Unable to upgrade %s to version %s', $this->getName().' '.$to, $from)
            );
	    }
	}
    
	/**
	 * Uninstall module
	 * 
	 * This method should not uninstall dependent modules, nor
	 * the module settings by default.
	 */
    public abstract function uninstall(array $options = array());

    /**
     * Retrieve service broker
     *
     * @return \Valu\Service\Broker
     */
    public function getServiceBroker()
    {
        if(!$this->serviceBroker){
            $this->serviceBroker = $this->getServiceLocator()->get('ServiceBroker');
        }
        
    	return $this->serviceBroker;
    }
    
    /**
     * Set service broker
     *
     * @param \Valu\Service\Broker $broker
     * @return \Valu\Service\AbstractSetup
     */
    public function setServiceBroker(Broker $broker)
    {
    	$this->serviceBroker = $broker;
    	return $this;
    }
    
    /**
     * Retrieve service loader instance
     * 
     * @return \Valu\Service\Loader
     */
    public function getServiceLoader(){
        return $this->getServiceBroker()->getLoader();
    }
    
    /**
     * Direct access to service utilities
     * 
     * @return \Valu\Service\Setup\Utils
     */
    protected function utils()
    {
        if(!$this->utils){
            $this->utils = $this->getServiceLocator()->get('SetupUtils');
        }
        
        return $this->utils;
    }
    
    /**
     * Trigger callback
     * 
     * @param string $callback Operation name
     * @param array|null $callbackArgs Arguments
     * @throws \Exception
     */
    protected function triggerCallback($callback, $callbackArgs = null){
         
        if(!$this->utils()->hasSetupService($this->getName())){
            throw new \Exception(sprintf('Callback service %s not available', $this->getName()));
        }
         
        $this->utils()->initSetupService($this->getName())->fork(
            $callback,
            $callbackArgs
        );
    }
}