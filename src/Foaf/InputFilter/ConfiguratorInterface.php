<?php
namespace Foaf\InputFilter;

interface ConfiguratorInterface
{
    /**
     * Configure input filter by name
     * 
     * @param string $name
     * @return \Zend\InputFilter\InputFilterInterface
     */
    public function configure($name);
    
    /**
     * Get input filter specifications by name
     * 
     * @param string $name
     * @return array
     */
    public function getSpecifications($name);
}