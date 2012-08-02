<?php
namespace Foaf\Model\ServiceManager;

use Foaf\Model\ArrayAdapter;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ArrayAdapterFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config    = $serviceLocator->get('Configuration');
        $modelConfig  = $config['model_framework'] ?: array();

        $adapter = ArrayAdapter::getSharedInstance();
        
        if(isset($modelConfig['array_adapter']) && isset($modelConfig['array_adapter']['cache_adapter'])){
            $adapter->setOptions(array(
                'cache_adapter' => $modelConfig['array_adapter']['cache_adapter']       
            ));
        }
        
        return $adapter;
    }
}