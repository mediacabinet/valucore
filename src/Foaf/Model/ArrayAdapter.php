<?php
namespace Foaf\Model;

use Zend\Cache\StorageFactory;

class ArrayAdapter{
    
    /**
     * Cache settings
     *
     * @var array
     */
    private $cache = array(
        'adapter' => array('name' => null),
        'plugins' => array('serializer')
    );
    
    private static $sharedInstance;
    
    public function __construct($options = null)
    {
        if(null !== $options){
            $this->setOptions($options);
        }
    }
    
    public function setOptions($options)
    {
        if (!is_array($options) && !$options instanceof \Traversable) {
            throw new \InvalidArgumentException(sprintf(
                    'Expected an array or Traversable; received "%s"',
                    (is_object($options) ? get_class($options) : gettype($options))
            ));
        }
        
        foreach ($options as $key => $value){
            	
            $key = strtolower($key);
            	
            if($key == 'cache_adapter'){
                	
                if(is_string($value)){
                    $value = array(
                        'name' => $value,
                        'options' => array()
                    );
                }
                else if(!isset($value['name'])){
                    throw new \InvalidArgumentException('Cache adapter name is missing');
                }
                else if(!isset($value['options'])){
                    $value['options'] = array();
                }
                	
                $this->setCacheAdapter($value['name'], $value['options']);
            }
        }
    
        return $this;
    }
    
    /**
     * Set cache adapter settings
     *
     * @param string $name
     * @param array $options
     */
    public function setCacheAdapter($name, array $options = array())
    {
        $this->cache['adapter']['name'] = $name;
        $this->cache['adapter']['options'] = $options;
    }

    /**
     * Populate object from array
     * 
     * @param \stdClass $object Object to populate
     * @param array $specs Specs to populate object with
     * @param unknown_type $options
     * @throws \InvalidArgumentException
     */
	public function fromArray($object, array $specs, $options = null){

	    if(!is_object($object)){
	        throw new \InvalidArgumentException(
                'Invalid value for argument $object; '.gettype($object).' given, object expexted'
	        );
	    }
	    
	    $definition = $this->getClassDefinition(get_class($object));
	    $setters    = $definition['setters'];
	    
		if(sizeof($specs)){
			foreach ($specs as $spec => $value) {
			    
				$method = isset($setters[$spec]) ? $setters[$spec] : null;
				
				if($method){
					$object->{$method}($value);
				}
			}
		}
	}
	
	/**
	 * Transfer object into array
	 * 
	 * @param array $properties Properties to fetch from the object
	 * @return array
	 */
	public function toArray($object, $properties = null, $options = null){
	    
	    if(!is_object($object)){
	        throw new \InvalidArgumentException(
                'Invalid value for argument $object; '.gettype($object).' given, object expexted'
            );
	    }
	    
	    $definition = $this->getClassDefinition(get_class($object));
	    $getters    = $definition['getters'];
	    $specs      = array();
	    
	    if(is_null($properties)){
	        $properties = array_keys($getters);
	    }
	    
	    if(!empty($properties)){
	        foreach ($properties as $property){
	            if(!isset($getters[$property])) continue;
	            
	            $method = $getters[$property];
	            $specs[$property] = $object->{$method}();
	        }
	    }
	    
		return $specs;
	}
	
	/**
	 * Retrieve shared ArrayAdapter instance
	 * 
	 * @return \Foaf\Model\ArrayAdapter
	 */
	public static function getSharedInstance()
	{
	    if(!self::$sharedInstance){
	        self::$sharedInstance = new ArrayAdapter;
	    }
	    
	    return self::$sharedInstance;
	}
	
	/**
	 * Retrieve class definition
	 * 
	 * Definition is an array in form
	 * array(
	 *     'getters' => array('property1' => 'getProperty1', ...)
	 *     'setters' => array('property1' => 'setProperty1', ...)
	 * )
	 * 
	 * @param unknown_type $class
	 * @return Ambigous <multitype:, string>
	 */
	private function getClassDefinition($class)
	{
	    $cache = $this->getCacheAdapter();
	    
	    $className = $class instanceof \ReflectionClass
	        ? $class->getName() : (string) $class;
	    
	    // Make class name valid for cache adapters
	    $cacheId = str_replace('\\', '_', $className);
	    
	    /**
	     * Fetch from cache
	     */
	    if ($cache && $cache->hasItem($cacheId)) {
	        $definition = $cache->getItem($cacheId);
	    }
	    /**
	     * Parse definition
	     */
	    else{
	        $definition = $this->parseClassDefinition($class);
	        
	        /**
	         * Cache definition
	         */
	        if ($cache) {
	            $cache->setItem($cacheId, $definition);
	        }
	    }
	    
	    return $definition;
	}
	
	/**
	 * Parse class definition
	 * 
	 * @param string $class
	 * @return array
	 */
	private function parseClassDefinition($class)
	{
	    if(is_string($class)){
	        $reflectionClass = new \ReflectionClass($class);
	    }
	    else if($class instanceof \ReflectionClass){
	        $reflectionClass = $class;
	    }
	    else{
	        throw new \InvalidArgumentException('Invalid class, string or ReflectionClass expected');
	    }

        $definition = array(
            'getters' => array(), 'setters' => array()
        );
	    
	    $properties = $reflectionClass->getProperties();
	     
	    $specs = array();
	    if(!empty($properties)){
	        foreach ($properties as $property){
	             
	            $name		= $property->getName();
	            $private 	= substr($name, 0, 1) == '_';
	             
	            if($private){
	                continue;
	            }
	             
	            $getter 	= 'get'.ucfirst($name);
	            $setter 	= 'set'.ucfirst($name);

	            if( $reflectionClass->hasMethod($getter) &&
                    $reflectionClass->getMethod($getter)->isPublic()){
	                 
	                $definition['getters'][$name] = $getter;
	            }
	            
	            if($reflectionClass->hasMethod($setter) &&
	                    $reflectionClass->getMethod($setter)->isPublic()){
	    
	                $definition['setters'][$name] = $setter;
	            }
	        }
	    }
	    
	    return $definition;
	}
	
	/**
	 * Get cache adapter
	 *
	 * @return \Zend\Cache\Storage\Adapter\AdapterInterface
	 */
	private function getCacheAdapter()
	{
	    if($this->cache['adapter']['name']){
	        return StorageFactory::factory($this->cache);
	    }
	    else{
	        return null;
	    }
	}
}