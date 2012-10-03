<?php
namespace Valu\Model\ArrayAdapter;

use ArrayObject;
use Valu\Model\ArrayAdapter;

class DateFormatterDelegate implements DelegateInterface
{
    
    protected $format = DATE_ATOM;
    
    public function __construct(array $options = array())
    {
        if (isset($options['format'])) {
            $this->setFormat($options['format']);
        }
    }
    
    public function filterOut(ArrayAdapter $arrayAdapter, $object, ArrayObject $data, array $fetch, array $options)
    {
        foreach($data as $key => &$value){
            if ($value instanceof \DateTime) {
                $value = $value->format($this->getFormat());
            }
        }
    }
    
    public function getFormat()
    {
        return $this->format;
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }
}