<?php
namespace Valu\InputFilter;

use Zend\InputFilter\InputFilterInterface;
use Zend\Cache\Storage\StorageInterface;

class InputFilterRepository
{
    
    /**
     * Cache namespace
     * 
     * @var string
     */
    const CACHE_NS = 'valu_input_filter_';
    
    /**
     * Input filter configurator
     * 
     * @var Configurator
     */
    private $configurator;
    
    /**
     * Input filter instances
     * 
     * @var array
     */
    private $inputFilters = array();
    
    /**
     * Cache
     * 
     * @var \Zend\Cache\Storage\StorageInterface
     */
    private $cache;
    
    /**
     * Get input filter
     * 
     * @return \Zend\InputFilter\InputFilterInterface
     */
    public function get($name)
    {
        if(!$name){
            return null;
        }
        
        if(!isset($this->inputFilters[$name])){
            
            $inputFilter = null;
            $cached = false;
            $cacheId = $this->getCacheId($name);
        
            if($this->getCache()){
                
                $inputFilter = $this->getCache()->getItem(
                    $cacheId
                );
                
                if($inputFilter){
                    $cached = true;
                }
            }
        
            if(!$inputFilter){
                $inputFilter = $this->getConfigurator()->configure($name);
            }
            
            if(!$cached && $this->getCache()){
                
                $this->getCache()->setItem(
                    $cacheId, 
                    $inputFilter);
            }
        
            $this->set($name, $inputFilter);
        }
        
        return $this->inputFilters[$name];
    }
    
    /**
     * Set input filter
     * 
     * @param InputFilterInterface $inputFilter
     */
    public function set($name, InputFilterInterface $inputFilter)
    {
        $this->inputFilters[$name] = $inputFilter;
    }
    
    /**
     * Retrieve configurator instance
     * 
     * @return \Valu\InputFilter\Configurator
     */
    public function getConfigurator()
    {
        if(!$this->configurator){
            $this->setConfigurator(new Configurator());
        }
        
        return $this->configurator;
    }
    
    /**
     * Set configurator instance
     * 
     * @param Configurator $configurator
     */
    public function setConfigurator(ConfiguratorInterface $configurator)
    {
        $this->configurator = $configurator;   
    }
    
    /**
	 * Set cache
	 *
	 * @param \Zend\Cache\Storage\StorageInterface $cache
	 * @return Acl
	 */
	public function setCache(StorageInterface $cache){
	    $this->cache = $cache;
	    return $this;
	}
	
	/**
	 * Retrieve cache
	 *
	 * @return \Zend\Cache\Storage\StorageInterface
	 */
	public function getCache(){
	    return $this->cache;
	}
    
    /**
     * Flush data, clear current input filter and cache record for
     * given namespace
     * 
     */
    public function reload($name)
    {
       $this->cascadeReload($name, array());
    }
    
    /**
     * Cascade reload to referenced input filters
     * 
     * @param string $name
     * @param array $reloaded
     */
    protected function cascadeReload($name, array & $reloaded)
    {
        // Skip, if already reloaded
        if(in_array($name, $reloaded)){
            return;
        }
    
        if(isset($this->inputFilters[$name])){
            unset($this->inputFilters[$name]);
            unset($this->inputFilterHashes[$name]);
        }
    
        // Remove from cache
        if($this->getCache()){
            $this->getCache()->removeItem($this->getCacheId($name));
        }
    
        // Mark reloaded
        $reloaded[] = $name;
    
        // Fetch references and reload each
        foreach($this->getConfigurator()->getReferences($name) as $name){
            $this->cascadeReload($name, $reloaded);
        }
    }
    
	/**
	 * Retrieve cache ID
	 * 
	 * @return string
	 */
	protected function getCacheId($name)
	{
	    return md5(self::CACHE_NS . $name);
	}
}