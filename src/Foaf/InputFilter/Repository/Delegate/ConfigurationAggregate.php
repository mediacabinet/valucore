<?php
namespace Foaf\InputFilter\Repository\Delegate;

use \ArrayObject;
use Foaf\InputFilter\Repository;
use Zend\InputFilter\InputFilterInterface;

class ConfigurationAggregate implements DelegateInterface
{
    /**
     * Configurations
     * 
     * @var array
     */
    protected $config;
    
    public function __construct(array $options)
    {
        if(isset($options['config'])){
            $this->setConfig($options['config']);
        }
    }

    public function getInputFilterSpecifications(Manager $manager, $name)
    {
        $config = $this->getConfig();
        
        if(isset($config[$name])){
            return $config[$name];
        }
        else{
            return array();
        }
    }
    
    public function prepareInputFilterSpecifications(Manager $manager, $name, ArrayObjectÊ$specifications)
    {}
    
    public function finalizeInputFilter(Manager $manager, $name, InputFilterInterface $inputFilter)
    {}
    
    public function getConfig()
    {
        if(is_string($this->config)){
            if(file_exists($this->config)){
                $this->config = \Zend\Config\Factory::fromFile($this->config);
            }
            else{
                throw new \InvalidArgumentException(
                    sprintf('Unable to load configurations from %s', $this->config)
                );
            }
        }
        
        return $this->config;
    }
    
    public function setConfig($config)
    {
        if(!is_string($config) && !is_array($config) && !($config instanceof \Traversable)){
            throw new \InvalidArgumentException('Config must be an array, Traversable object or filename');
        }
    
        $this->config = $config;
    }
}