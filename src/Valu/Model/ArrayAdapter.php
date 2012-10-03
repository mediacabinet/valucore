<?php
namespace Valu\Model;

use Zend\Stdlib\PriorityQueue;

use Zend\Cache\Storage\StorageInterface;

class ArrayAdapter
{

    /**
     * Cache
     * 
     * @var \Zend\Cache\Storage\StorageInterface
     */
    private $cache;
    
    /**
     * Adapter delegates
     * 
     * @var \Zend\Stdlib\PriorityQueue
     */
    private $delegates;
    
    /**
     * Shared array adapter instance
     *
     * @var ArrayAdapter
     */
    private static $sharedInstance;
    
    /**
     * Populate object from array
     *
     * @param \stdClass $object
     *            Object to populate
     * @param array $specs
     *            Specs to populate object with
     * @param unknown_type $options            
     * @throws \InvalidArgumentException
     */
    public function fromArray($object, array $specs, $options = null)
    {
        if (! is_object($object)) {
            throw new \InvalidArgumentException(
                    'Invalid value for argument $object; ' . gettype($object) .
                             ' given, object expexted');
        }
        
        $definition = $this->getClassDefinition(get_class($object));
        $setters = $definition['setters'];
        
        if (sizeof($specs)) {
            foreach ($specs as $spec => $value) {
                
                $method = isset($setters[$spec]) ? $setters[$spec] : null;
                
                if ($method) {
                    $object->{$method}($value);
                }
            }
        }
    }

    /**
     * Transfer object into array
     *
     * @param array $properties
     *            Properties to fetch from the object
     * @return array
     */
    public function toArray($object, $properties = null, $options = null)
    {
        if (! is_object($object)) {
            throw new \InvalidArgumentException(
                    'Invalid value for argument $object; ' . gettype($object) .
                             ' given, object expexted');
        }
        
        $options     = is_array($options) ? $options : array();
        $definition  = $this->getClassDefinition(get_class($object));
        $getters     = $definition['getters'];
        $specs       = new \ArrayObject();
        $delegates   = $this->getDelegates()->toArray();
        
        if (is_null($properties)) {
            $properties = array_keys($getters);
        }
        
        $properties = $this->normalizeToArrayProps($properties);

        if (! empty($properties)) {
            foreach ($properties as $key => $value) {

                if (!$value || !isset($getters[$key])) {
                    continue;
                }
                
                $method      = $getters[$key];
                $specs[$key] = $object->{$method}();
            }
        }
        
        foreach($delegates as $delegate){
            $delegate->filterOut($this, $object, $specs, $properties, $options);
        }
        
        return $specs->getArrayCopy();
    }
    
    /**
     * Retrieve delegate queue
     * 
     * @return \Zend\Stdlib\PriorityQueue
     */
    public function getDelegates()
    {
        if ($this->delegates == null) {
            $this->delegates = new PriorityQueue();
        }
        
        return $this->delegates;
    }

    /**
     * Retrieve class definition
     *
     * Definition is an array in form
     * array(
     * 'getters' => array('property1' => 'getProperty1', ...)
     * 'setters' => array('property1' => 'setProperty1', ...)
     * )
     *
     * @param unknown_type $class            
     * @return Ambigous <multitype:, string>
     */
    public function getClassDefinition($class)
    {
        $cache = $this->getCache();
        
        $className = $class instanceof \ReflectionClass ? $class->getName() : (string) $class;
        
        // Make class name valid for cache adapters
        $cacheId = str_replace('\\', '_', $className);
        
        /**
         * Fetch from cache or parse
         */
        if ($cache && $cache->hasItem($cacheId)) {
            $definition = $cache->getItem($cacheId);
        } else {
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
     * Get cache adapter
     *
     * @return \Zend\Cache\Storage\StorageInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set cache adapter
     *
     * @param \Zend\Cache\Storage\StorageInterface $cache            
     * @return \Valu\Model\ArrayAdapter
     */
    public function setCache(StorageInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }
    
    /**
     * Retrieve shared ArrayAdapter instance
     *
     * @return \Valu\Model\ArrayAdapter
     * @todo Get rid of this method!
     */
    public static function getSharedInstance()
    {
        if (! self::$sharedInstance) {
            self::$sharedInstance = new ArrayAdapter();
        }
        
        return self::$sharedInstance;
    }

    /**
     * Set shared ArrayAdapter instance
     * 
     * @param ArrayAdapter $arrayAdapter
     */
    public static function setSharedInstance(ArrayAdapter $arrayAdapter)
    {
        self::$sharedInstance = $arrayAdapter;
    }

    /**
     * Parse class definition
     *
     * @param string $class            
     * @return array
     */
    private function parseClassDefinition($class)
    {
        if (is_string($class)) {
            $reflectionClass = new \ReflectionClass($class);
        } elseif ($class instanceof \ReflectionClass) {
            $reflectionClass = $class;
        } else {
            throw new \InvalidArgumentException(
                    'Invalid class, string or ReflectionClass expected');
        }
        
        $definition = array(
                'getters' => array(),
                'setters' => array()
        );
        
        $properties = $reflectionClass->getProperties();
        
        $specs = array();
        if (! empty($properties)) {
            foreach ($properties as $property) {
                
                $name = $property->getName();
                $private = substr($name, 0, 1) == '_';
                
                if ($private || $property->isStatic()) {
                    continue;
                }
                
                $getter = 'get' . ucfirst($name);
                $setter = 'set' . ucfirst($name);
                
                if ($reflectionClass->hasMethod($getter) 
                    && $reflectionClass->getMethod($getter)->isPublic()
                    && !$reflectionClass->getMethod($getter)->isStatic()) {
                    
                    $definition['getters'][$name] = $getter;
                }
                
                if ($reflectionClass->hasMethod($setter) 
                    && $reflectionClass->getMethod($setter)->isPublic()
                    && !$reflectionClass->getMethod($setter)->isStatic()) {
                    
                    $definition['setters'][$name] = $setter;
                }
            }
        }
        
        return $definition;
    }
    
    private function normalizeToArrayProps($properties)
    {
        if (!is_array($properties)) {
            return array();
        }
        
        $normal = array();
        
        foreach($properties as $key => $value){
            if (is_numeric($key) && is_string($value)) {
                $normal[$value] = true;
            } else {
                $normal[$key] = is_array($value) ? $value : (bool) $value;
            }
        }
        
        return $normal;
    }
}