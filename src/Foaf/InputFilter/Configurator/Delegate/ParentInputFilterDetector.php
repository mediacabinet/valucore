<?php
namespace Foaf\InputFilter\Configurator\Delegate;

use \ArrayObject;
use Zend\Stdlib\ArrayUtils;
use Foaf\InputFilter\ConfiguratorInterface;
use Zend\InputFilter\InputFilterInterface;

class ParentInputFilterDetector implements DelegateInterface
{

    public function getInputFilterSpecifications(ConfiguratorInterface $configurator, $name)
    {}
    
    public function prepareInputFilterSpecifications(ConfiguratorInterface $configurator, $name, ArrayObject $specifications)
    {
        if(isset($specifications['type']) && !class_exists($specifications['type'])){
            
            $parent = $configurator->getSpecifications($specifications['type']);
            $copy   = $specifications->getArrayCopy();
            unset($copy['type']);
            $merged = ArrayUtils::merge($parent, $copy);
            
            $specifications->exchangeArray($merged);
        }
    }
    
    public function finalizeInputFilter(ConfiguratorInterface $configurator, $name, InputFilterInterface $inputFilter)
    {}
}