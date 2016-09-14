<?php
namespace Valu\Model;

use Zend\EventManager\EventInterface;

use ArrayObject;
use Valu\Model\ArrayAdapter\ProviderInterface;
use Zend\EventManager\Event;
use Zend\Cache\Storage\StorageInterface;
use Zend\EventManager\EventManager;

class ArrayAdapter
{

    const WILDCHAR = '*';

    const CACHE_PREFIX = 'valu_array_adapter_';
    
    /**
     * Cache
     * 
     * @var \Zend\Cache\Storage\StorageInterface
     */
    private $cache;
    
    /**
     * Event manager instance
     * 
     * @var \Zend\EventManager\EventManager
     */
    private $eventManager;
    
    /**
     * Whether or not scalar values should be extracted
     * silently
     * 
     * @var boolean
     */
    private $extractScalarsSilently = true;
    
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
        $getters = $definition['getters'];
        
        if (sizeof($specs)) {
            foreach ($specs as $spec => $value) {
                
                $method = isset($setters[$spec]) ? $setters[$spec] : null;
                
                // If array provided and target is an object
                if (is_array($value) && isset($getters[$spec])) {
                    
                    $getter       = $getters[$spec];
                    $currentValue = $object->{$getter}();
                    
                    if (is_object($currentValue)) {
                        if ($currentValue instanceof ProviderInterface) {
                            $currentValue->getArrayAdapter()->
                                fromArray($currentValue, $value, $options);
                        } else {
                            $this->fromArray($currentValue, $value, $options);
                        }
                        
                        continue; // Skip to next
                    }
                } 
                
                if ($method) {
                    $object->{$method}($value);
                }
            }
        }
    }

    /**
     * Transfer object into array
     *
     * @param array $extract
     *            Properties to extract from the object
     * @return array
     */
    public function toArray($object, $extract = null, $options = null)
    {
        if (! is_object($object)) {
            throw new \InvalidArgumentException(
                    'Invalid value for argument $object; ' . gettype($object) .
                             ' given, object expexted');
        }
        
        $options     = is_array($options) ? $options : array();
        $definition  = $this->getClassDefinition(get_class($object));
        $getters     = $definition['getters'];
        $data        = new ArrayObject();
        
        if (is_null($extract)) {
            $extract = array_fill_keys(array_keys($getters), true);
        } elseif (is_string($extract)) {
            $extract = array($extract => true);
        } elseif (is_array($extract) && array_key_exists(self::WILDCHAR, $extract) && $extract[self::WILDCHAR]) {
            $extractExplicit = $extract;
            unset($extractExplicit[self::WILDCHAR]);

            $extractImplicit = array_fill_keys(array_keys($getters), true);
            $extract = array_merge($extractImplicit, $extractExplicit);
        }

        $eventParams = new ArrayObject([
            'object'  => $object,
            'extract' => $extract,
            'data'    => null,
            'options' => $options
        ]);
        
        $this->getEventManager()->trigger('pre.toArray', $this, $eventParams);
        
        if (!empty($eventParams['extract'])) {
            
            foreach ($eventParams['extract'] as $key => $value) {

                if (!$value || !isset($getters[$key])) {
                    continue;
                }
                
                $method     = $getters[$key];
                $data[$key] = $object->{$method}();
            }
            
            if (sizeof($data)) {
                $eventPrototype = new Event('extract', $this, $eventParams);
                $this->traverseAndExtract($eventPrototype, $data, $eventParams['extract']);
            }
        }
        
        if (array_key_exists('spec', $eventParams)) {
            unset($eventParams['spec']);
        }
        
        $eventParams['data'] = $data;
        
        $this->getEventManager()->trigger('post.toArray', $this, $eventParams);

        return $data->getArrayCopy();
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
        $cacheId = self::CACHE_PREFIX . str_replace('\\', '_', $className);
        
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
     * @return \Zend\EventManager\EventManager
     */
    public function getEventManager()
    {
        if (!$this->eventManager) {
            $this->setEventManager(new EventManager());
        }
        
        return $this->eventManager;
    }

	/**
	 * Should scalars be extracted silently (no event
	 * is triggered)?
	 * 
     * @return boolean
     */
    public function getExtractScalarsSilently()
    {
        return $this->extractScalarsSilently;
    }

	/**
	 * Enable/disable silent extraction for scalar values
	 * 
     * @param boolean $extractScalarsSilently
     */
    public function setExtractScalarsSilently($extractScalarsSilently = true)
    {
        $this->extractScalarsSilently = $extractScalarsSilently;
    }

	/**
     * @param \Zend\EventManager\EventManager $eventManager
     */
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;
    }
    
    /**
     * Traverse and extract data recursively
     * 
     * @param EventInterface $event Event prototype
     * @param \ArrayAccess $data
     * @param array|boolean $extract
     */
    private function traverseAndExtract(EventInterface $event, \ArrayAccess $data, $extract)
    {
        // Fetch keys
        $keys = array();
        foreach ($data as $key => &$value) {
            $keys[] = $key;
        }
        
        // Iterate keys
        foreach ($keys as $key) {
            
            if (is_numeric($key)) {
                $extractNext = $extract;
            } elseif($extract === true) {
                $extractNext = $extract;
            } elseif (is_array($extract) && array_key_exists($key, $extract)) {
                $extractNext = $extract[$key];
            } else {
                $extractNext = false;
            }
            
            if (!is_array($extractNext) && $extractNext != true) {
                unset($data[$key]);
                continue;
            }
            
            if (is_array($data[$key])) {
                $data[$key] = new ArrayObject($data[$key]);
            }
            
            if ($data[$key] instanceof \ArrayAccess) {
                $this->traverseAndExtract($event, $data[$key], $extractNext);
            }
            
            if (!array_key_exists($key, $data)) {
                continue;
            }

            if (!is_scalar($data[$key]) || !$this->getExtractScalarsSilently()) {
                $event->setParam('spec', $key);
                $event->setParam('extract', $extractNext);
                $event->setParam('data', $data);
                
                $this->getEventManager()->trigger($event);
            }
            
            if (isset($data[$key]) && $data[$key] instanceof ArrayObject) {
                $data[$key] = $data[$key]->getArrayCopy();
            }
        }
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
}