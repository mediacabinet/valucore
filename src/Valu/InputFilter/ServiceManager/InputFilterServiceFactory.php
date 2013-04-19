<?php
namespace Valu\InputFilter\ServiceManager;

use Valu\InputFilter\Service\InputFilterService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class InputFilterServiceFactory
    implements FactoryInterface
{
    
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $manager = $serviceLocator->get('valu.inputfilter.repository');
        
        $service = new InputFilterService($manager);
        return $service;
    }
}