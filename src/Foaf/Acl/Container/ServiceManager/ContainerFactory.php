<?php
namespace Foaf\Service\ServiceManager;

use Foaf\Acl\Container;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ContainerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config    = $serviceLocator->get('Configuration');
        $contexts  = $config['acl_contexts'] ?: array();
        
        $container = new Container();
        $container->getFactoryManager()->setServiceLocator($serviceLocator);
        
        if(is_array($contexts)){
            foreach($contexts as $context => $specs){
                if(isset($specs['service'])){
                    $container->registerContext($context, $specs['service']);
                }
            }
        }
        
        return $container;
    }
}