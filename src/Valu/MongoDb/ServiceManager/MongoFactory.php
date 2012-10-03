<?php
namespace Valu\MongoDb\ServiceManager;

use \Mongo;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MongoFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Configuration');
        
        $server = isset($config['mongodb']['server'])
            ? $config['mongodb']['server'] : null;
        
        if (isset($config['mongodb']['database'])) {
            $server .= '/' . $config['mongodb']['database'];
        }
        
        $mongo = new Mongo($server);
        return $mongo;
    }
}