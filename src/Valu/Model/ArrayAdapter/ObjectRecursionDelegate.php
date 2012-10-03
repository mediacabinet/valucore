<?php
namespace Valu\Model\ArrayAdapter;

use ArrayObject;
use stdClass;
use Valu\Model\ArrayAdapter;

class ObjectRecursionDelegate implements DelegateInterface
{
    
    protected $namespaces = array();
    
    public function __construct(array $options = array())
    {
        if (isset($options['namespaces'])) {
            $this->setNamespaces($options['namespaces']);
        }
    } 
    
    public function filterOut(ArrayAdapter $arrayAdapter, $object, ArrayObject $data, array $fetch, array $options)
    {
        foreach ($data as $key => &$value) {
            if (is_object($value)
                && $this->isInValidNamespace($object, $value)) {
                
                if ($fetch[$key] == true) {
                    $fetch[$key] = null;
                }
                
                if (($value instanceof ProviderInterface)) {
                    $value = $value->getArrayAdapter()->
                        toArray($value, $fetch[$key], $options);
                } else {
                    $value = $arrayAdapter->toArray($value, $fetch[$key], $options);
                }
                
            }
        }
    }
    
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;
        return $this;
    }
    
    public function getNamespaces()
    {
        return $this->namespaces;
    }
    
    protected function isInValidNamespace($refObject, $object)
    {
        if (sizeof($this->namespaces) == 0) {
            return true;
        } elseif($refObject instanceof $object) {
            return true;
        } else {
            foreach ($this->getNamespaces() as $ns) {
                if ($object instanceof $ns) {
                    return true;
                }
            }
        }
        
        return false;
    }
}