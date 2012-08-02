<?php
namespace Foaf\Service\ServiceManager;

use Foaf\Service\Setup\Utils,
    Zend\ServiceManager\FactoryInterface,
    Zend\ServiceManager\ServiceLocatorInterface;

class SetupUtilsFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $broker = $serviceLocator->get('ServiceBroker');
        $config = $serviceLocator->get('Configuration');
        $services = $config['setup_utils'] ?: array();
        
        $utils = new Utils(
            $broker,
            $config
        );
        
        return $utils;
    }
}