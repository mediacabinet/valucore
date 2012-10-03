<?php
namespace Valu\MongoDb\ServiceManager;

use \Mongo;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MongoDbFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Configuration');
        $mongo  = $serviceLocator->get('Mongo');

        if (isset($config['mongodb']['database'])) {
            return $mongo->selectDB($config['mongodb']['database']);
        } else {
            return null;
        }
    }
}