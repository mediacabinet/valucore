<?php
namespace ValuTest\InputFilter\TestAsset;

use Zend\InputFilter\Input as BaseInput;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Input extends BaseInput implements ServiceLocatorAwareInterface
{
    protected $serviceLocator;

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        
        $this->getFilterChain()
            ->getPluginManager()
            ->setServiceLocator($serviceLocator);
        
        $this->getValidatorChain()
            ->getPluginManager()
            ->setServiceLocator($serviceLocator);
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}
