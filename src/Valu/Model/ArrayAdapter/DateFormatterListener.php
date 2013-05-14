<?php
namespace Valu\Model\ArrayAdapter;

use ArrayObject;
use Valu\Model\ArrayAdapter;
use Zend\EventManager\EventInterface;

class DateFormatterListener
{
    protected $defaultFormat = DATE_ISO8601;
    
    public function __invoke(EventInterface $event)
    {
        $data    = $event->getParam('data');
        $spec    = $event->getParam('spec');
        
        if (!isset($data[$spec])) {
            return;
        }
        
        $value = $data[$spec];
        
        if (!$value instanceof \DateTime) {
            return;
        }
        
        $extract = $event->getParam('extract', []);
        $options = $event->getParam('options');
        
        if (isset($options['date_formatter']) && isset($options['date_formatter']['format'])) {
            $format = $options['date_formatter']['format'];
        } else {
            $format = $this->defaultFormat;
        }
        
        $data[$spec] = $value->format($format);
    }
}