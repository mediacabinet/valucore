<?php
namespace Foaf\InputFilter\Repository\Delegate;

use \ArrayObject;
use Foaf\InputFilter\Repository;
use Zend\InputFilter\InputFilterInterface;

interface DelegateInterface
{
    /**
     * Retrieve input filter description as an
     * array
     * 
     * @param string $name
     */
    public function getInputFilterSpecifications(Repository $repository, $name);
    
    /**
     * Prepare input filter specifications
     * 
     * @param Manager $manager
     * @param string $name
     * @param arrayÊ $specifications
     */
    public function prepareInputFilterSpecifications(Repository $repository, $name, ArrayObjectÊ$specifications);
    
    /**
     * Finalize input filter
     * 
     * @param Manager $manager
     * @param string $name
     * @param InputFilterInterface $inputFilter
     */
    public function finalizeInputFilter(Repository $repository, $name, InputFilterInterface $inputFilter);
}