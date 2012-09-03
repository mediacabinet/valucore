<?php
namespace Foaf\InputFilter\ServiceManager;

use Foaf\InputFilter\Service\InputFilterService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class InputFilterServiceFactory
    implements FactoryInterface
{
    
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $manager = $serviceLocator->get('FoafInputFilterRepository');
        
        $service = new InputFilterService($manager);
        return $service;
    }
}