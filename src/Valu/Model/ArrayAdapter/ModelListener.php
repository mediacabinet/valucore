<?php
namespace Valu\Model\ArrayAdapter;

use ArrayObject;
use Valu\Model\ArrayAdapter;
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
    public function __invoke(EventInterface $event)
    {
        $data    = $event->getParam('data');
        $spec    = $event->getParam('spec');
        
        if (!isset($data[$spec])) {
            return;
        }
        
        $value = $data[$spec];
        
        if (!is_object($value)) {
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
        }
    }
}