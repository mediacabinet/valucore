<?php
namespace Foaf\Service;

use Zend\ServiceManager\AbstractPluginManager;

class ServiceManager extends AbstractPluginManager
{
    
    private $instanceNames = array();
    
    private $instanceOptions = array();
    
    /**
     * Retrieve a service from the manager by name
     *
     * Allows passing an array of options to use when creating the instance.
     * createFromInvokable() will use these and pass them to the instance
     * constructor if not null and a non-empty array.
     *
     * @param  string $name
     * @param  array $options
     * @param  bool $usePeeringServiceManagers
     * @return object
     */
    public function get($name, $options = array(), $usePeeringServiceManagers = true)
    {
        $this->instanceNames[]     = $name;
        $this->instanceOptions[]   = $options;
        
    	$instance = parent::get($name, $options, $usePeeringServiceManagers);
    	
    	array_pop($this->instanceNames);
        array_pop($this->instanceOptions);
        
    	return $instance;
    }
    
    public function getCreationInstanceName()
    {
        return  sizeof($this->instanceNames)
                ? $this->instanceNames[sizeof($this->instanceNames)-1]
                : null;
    }
    
    public function getCreationInstanceOptions()
    {
        return  sizeof($this->instanceOptions)
        ? $this->instanceOptions[sizeof($this->instanceOptions)-1]
        : null;
    }
    
    public function validatePlugin($plugin)
    {
        if( is_object($plugin) &&
    		!($plugin instanceof ServiceInterface)){
        	 
        	return false;
        }
        else{
        	return true;
        }
    }
}