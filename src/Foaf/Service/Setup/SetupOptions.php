<?php
namespace Foaf\Service\Setup;

use Zend\Stdlib\AbstractOptions;

class SetupOptions extends AbstractOptions{
    
    /**
     * Module version
     * 
     * @var string
     */
    protected $version = '';
    
    /**
     * Module dependencies
     * 
     * @var array
     */
    protected $dependencies = array();
    
	/**
	 * @return the $version
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @return the $dependencies
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	/**
	 * @param string $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * @param array $dependencies
	 */
	public function setDependencies(array $dependencies) {
	    
	    $this->dependencies = array();
	    
		foreach($dependencies as $key => &$specs){
		    
		    isset($specs['module']) || $specs['module'] = $key;
		    isset($specs['options']) && is_array($specs['options']) || $specs['options'] = null;
		    
		    if(!isset($specs['version'])){
		    	throw new \InvalidArgumentException('Missing version from dependency information');
		    }
		}
		
		$this->dependencies = $dependencies;
	}

}