<?php
namespace Valu\Service\ServiceManager;

use Valu\Service\Setup\Utils,
    Zend\ServiceManager\FactoryInterface,
    Zend\ServiceManager\ServiceLocatorInterface;

class SetupUtilsFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $broker = $serviceLocator->get('ServiceBroker');
        $config = $serviceLocator->get('Configuration');
        $config = $config['setup_utils'] ?: array();
        
        $utils = new Utils(
            $broker,
            $config
        );
        
        return $utils;
    }
}