<?php
namespace Foaf\InputFilter\Repository\Delegate;

use \ArrayObject;
use Zend\Stdlib\ArrayUtils;
use Foaf\InputFilter\Repository;
use Zend\InputFilter\InputFilterInterface;

class ParentInputFilterDetector implements DelegateInterface
{

    public function getInputFilterSpecifications(Manager $manager, $name)
    {}
    
    public function prepareInputFilterSpecifications(Manager $manager, $name, ArrayObjectÊ$specifications)
    {
        if(isset($specifications['type']) && !class_exists($specifications['type'])){
            
            $parent = $manager->getSpecifications($specifications['type']);
            $copy   = $specifications->getArrayCopy();
            $merged = ArrayUtils::merge($parent, $copy);
            
            $specifications->exchangeArray($merged);
        }
    }
    
    public function finalizeInputFilter(Manager $manager, $name, InputFilterInterface $inputFilter)
    {}
}