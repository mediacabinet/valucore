<?php
namespace Foaf\InputFilter\Repository\Delegate;

use \ArrayObject;
use Foaf\InputFilter\Repository;
use Zend\InputFilter\InputFilterInterface;

class ChildInputFilterDetector implements DelegateInterface
{

    public function getInputFilterSpecifications(Manager $manager, $name)
    {}
    
    public function prepareInputFilterSpecifications(Manager $manager, $name, ArrayObjectÊ$specifications)
    {
        // Locate sub input filters and process them separately
        foreach($specifications as $name => $input){
            if(isset($input['type'])){
                if(!class_exists($input['type'])){
                    $specifications[$name] = $manager->get($input['type']);
                }
            }
        }
    }
    
    public function finalizeInputFilter(Manager $manager, $name, InputFilterInterface $inputFilter)
    {}
}