<?php
namespace Valu\Model\ArrayAdapter;

use ArrayObject;
use Valu\Model\ArrayAdapter;

class ArrayRecursionDelegate implements DelegateInterface
{
    public function filterOut(ArrayAdapter $arrayAdapter, $object, ArrayObject $data, array $fetch, array $options)
    {
        foreach ($data as $key => &$value) {
            if (is_array($value) && is_array($fetch[$key])){
               
                $fetchArray = $fetch[$key];
                
                foreach ($value as $k => $v) {
                    if (!array_key_exists($k, $fetchArray) || $fetchArray[$k] === false) {
                        unset($value[$k]);
                    }
                }
            }
        }
    }
}