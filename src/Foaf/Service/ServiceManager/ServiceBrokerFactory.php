<?php
namespace Foaf\Service\ServiceManager;

use Foaf\Service\Broker,
    Foaf\Service\Loader,
    Zend\ServiceManager\FactoryInterface,
    Zend\ServiceManager\ServiceLocatorInterface;

class ServiceBrokerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config    = $serviceLocator->get('Configuration');
        $services  = $config['services'] ?: array();
        
        $scopedLocator = $serviceLocator->createScopedServiceManager();
        
        $loader = new Loader(
            array(
                'services'	=> $services,
                'locator'	=> $scopedLocator,
                'cache_adapter' => $config['service_broker']['cache_adapter']
            )
        );
        
        // Look for services from global service locator as well
        $loader->getPluginManager()->addPeeringServiceManager($serviceLocator);
        
        $broker = new Broker(array('loader' => $loader));
        return $broker;
    }
}