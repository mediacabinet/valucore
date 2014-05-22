<?php
namespace Valu\Model\ArrayAdapter;

use ArrayObject;
use Zend\EventManager\EventInterface;

/**
 * Model listener
 * 
 * This class is a listener that tries to recognize objects that
 * are model classes and convert them to array using the best
 * available method, which is either getting the model's ID or
 * using its internal toArray implementation or toArray provided
 * by model's getArrayAdapter method.
 */
class ModelListener
{
    /**
     * Array of allowed namespaces
     * 
     * @var array
     */
    private $namespaces = [];
    
    /**
     * Array of proxy namespaces
     * 
     * @var array
     */
    private $proxyNamespaces = [];
    
    public function __construct($options)
    {
        if (isset($options['namespaces'])) {
            $this->setNamespaces($options['namespaces']);
        }
        
        if (isset($options['proxy_namespaces'])) {
            $this->setProxyNamespaces($options['proxy_namespaces']);
        }
    }
    
    public function __invoke(EventInterface $event)
    {
        $data    = $event->getParam('data');
        $spec    = $event->getParam('spec');
        
        if (!isset($data[$spec])) {
            return;
        }
        
        $value = $data[$spec];

        if (!is_object($value) || !$this->classIsInAllowedNamespace(get_class($value))) {
            return;
        }
        
        $extract = $event->getParam('extract', []);
        $options = $event->getParam('options');
        
        if ((is_array($extract) && empty($extract)) || (!is_array($extract) && $extract)) {
            if(isset($value->__identifier__)) {
                $data[$spec] = $value->__identifier__;
            } elseif(method_exists($value, 'getId')) {
                $data[$spec] = $value->getId();
            } else {
                // do nothing, use as is
                return;
            }
        } elseif(method_exists($value, 'getArrayAdapter')) {
            $data[$spec] = $value->getArrayAdapter()->toArray($value, $extract, $options);
        } else {
            $data[$spec] = $event->getTarget()->toArray($value, $extract, $options);
        }
    }
    
    /**
     * Set allowed namespaces
     * 
     * @param array $namespaces
     */
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;
    }
    
    /**
     * Get allowed namespaces
     * 
     * @return array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }
    
    /**
     * Retrieve proxy namespaces
     * 
     * @return array
     */
    public function getProxyNamespaces()
    {
        return $this->proxyNamespaces;
    }

	/**
	 * Set proxy namespaces
	 * 
     * @param array $proxyNamespaces
     */
    public function setProxyNamespaces(array $proxyNamespaces)
    {
        $this->proxyNamespaces = $proxyNamespaces;
    }

	/**
     * Test whether or not class is in one of the allowed
     * namespaces
     * 
     * @param string $class
     * @return boolean
     */
    public function classIsInAllowedNamespace($class)
    {
        foreach ($this->namespaces as $ns) {
            $canonicalClass = $this->canonicalizeClass($class);
            if (strpos($canonicalClass, $ns) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Parse canonical class name by removing any proxy
     * class prefix
     * 
     * @param string $class
     * @return string|unknown
     */
    private function canonicalizeClass($class)
    {
        foreach ($this->proxyNamespaces as $proxyNs) {
            if (strpos($class, $proxyNs) === 0) {
                return ltrim(substr($class, strlen($proxyNs)), "\\");
            }
        }
        
        return $class;
    }
}