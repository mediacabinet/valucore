<?php
namespace Valu\InputFilter\Configurator\Delegate;

use \ArrayObject;
use Valu\InputFilter\ConfiguratorInterface;
use Zend\InputFilter\InputFilterInterface;

class ChildInputFilterDetector implements DelegateInterface
{

    public function getInputFilterSpecifications(ConfiguratorInterface $configurator, $name)
    {}
    
    public function prepareInputFilterSpecifications(ConfiguratorInterface $configurator, $name, ArrayObject $specifications)
    {
        // Locate sub input filters and process them separately
        foreach($specifications as $name => $input){
            if(isset($input['type']) && !class_exists($input['type'])){
                $specifications[$name] = $configurator->configure($input['type']);
            }
        }
    }
    
    public function finalizeInputFilter(ConfiguratorInterface $configurator, $name, InputFilterInterface $inputFilter)
    {}
}