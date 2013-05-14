<?php
namespace Valu\Model\ArrayAdapter;

use ArrayAccess;
use ArrayObject;
use Valu\Model\ArrayAdapter;
use Zend\EventManager\EventInterface;

class RecursionListener
{
    public function __invoke(EventInterface $event)
    {
        $data    = $event->getParam('data');
        $spec    = $event->getParam('spec');
        
        if (!isset($data[$spec])) {
            return;
        }
        
        $value = $data[$spec];
        
        if (!is_array($value) && !$value instanceof ArrayAccess) {
            return;
        }
        
        $extract = $event->getParam('extract', []);
        $options = $event->getParam('options');
        
        $eventParams = [
            'data'    => $value,
            'extract' => null,
            'options' => $options
        ];

        foreach ($value as $key => &$value) {
            
            if (!is_numeric($key) 
                && (!array_key_exists($key, $extract) 
                    || $extract[$key] === false)) {
                
                unset($data[$spec][$k]);
                continue;
            }
            
            if (is_numeric($key)) {
                $eventParams['extract'] = $extract;
            } else {
                $eventParams['extract'] = $extract[$key];
            }
            
            $eventParams['spec'] = $key; 
            
            $event->getTarget()->getEventManager()->trigger(
                'extract', 
                $event->getTarget(),
                $eventParams);
        }
    }
}