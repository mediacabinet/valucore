<?php
namespace Valu\Model\ArrayAdapter;

use ArrayObject;
use Valu\Model\ArrayAdapter;

class RecursionDelegate implements DelegateInterface
{
    private $options;
    
    public function filterOut(ArrayAdapter $arrayAdapter, $object, ArrayObject $data, array $fetch, array $options)
    {
        foreach ($data as $key => &$value) {
            $this->filterValue($value, $fetch[$key], $options);
        }
    }
    
    protected function filterValue(&$value, $fetch, &$options)
    {
        if ((is_array($fetch) && empty($fetch)) || (!is_array($fetch) && $fetch)) {
            // do nothing, use as is
            return;
        } elseif (is_array($value) || $value instanceof \ArrayAccess){
            foreach ($value as $k => &$v) {
                if (is_numeric($k)) {
                    $this->filterValue($v, $fetch, $options);
                } elseif (!array_key_exists($k, $fetch) || $fetch[$k] === false) {
                    unset($value[$k]);
                } else {
                    $this->filterValue($v, $fetch[$k], $options);
                }
            }
        } elseif(is_object($value) && method_exists($value, 'getArrayAdapter')) {
            $value = $value->getArrayAdapter()->toArray($value, $fetch, $options);
        }
    }
}