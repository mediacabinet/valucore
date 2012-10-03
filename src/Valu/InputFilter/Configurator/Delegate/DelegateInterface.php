<?php
namespace Valu\InputFilter\Configurator\Delegate;

use \ArrayObject;
use Valu\InputFilter\ConfiguratorInterface;
use Zend\InputFilter\InputFilterInterface;

interface DelegateInterface
{
    /**
     * Retrieve input filter description as an
     * array
     * 
     * @param Configurator $configurator
     * @param string $name
     */
    public function getInputFilterSpecifications(ConfiguratorInterface $configurator, $name);
    
    /**
     * Prepare input filter specifications
     * 
     * @param Configurator $configurator
     * @param string $name
     * @param ArrayObjectÊ $specifications
     */
    public function prepareInputFilterSpecifications(ConfiguratorInterface $configurator, $name, ArrayObject $specifications);
    
    /**
     * Finalize input filter
     * 
     * @param Configurator $configurator
     * @param string $name
     * @param InputFilterInterface $inputFilter
     */
    public function finalizeInputFilter(ConfiguratorInterface $configurator, $name, InputFilterInterface $inputFilter);
}