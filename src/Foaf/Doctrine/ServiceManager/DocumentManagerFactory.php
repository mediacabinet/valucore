<?php
namespace Foaf\Doctrine\ServiceManager;

use Foaf\Service\Broker;
use Foaf\Service\Loader;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Doctrine\ODM\MongoDB\Configuration as MongoDbConfig;
use Doctrine\ODM\MongoDB\Mapping\Driver\DriverChain as MongoDbDriverChain;
use Doctrine\MongoDB\Connection;
use Doctrine\Common\Annotations\AnnotationRegistry;

class DocumentManagerFactory implements FactoryInterface
{
    
    protected $odmConfig;
    
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Configuration');
        $this->init($config);
		
		$connection = new Connection(
			$config['doctrine']['mongodb']['server']
		);
			
		$dm = \Doctrine\ODM\MongoDB\DocumentManager::create(
	        $connection, 
	        $this->odmConfig
        );
		
		return $dm;
    }
    
    protected function init($config){
        
        if($this->odmConfig){
            return;
        }
        
        /**
         * Configure MongoDB proxy and hydrator
         */
        $appConfig = $config['doctrine']['mongodb'];
         
        $odmConfig = new MongoDbConfig;
        $odmConfig->setProxyDir($appConfig['proxy_dir']);
        $odmConfig->setProxyNamespace($appConfig['proxy_ns']);
        
        $odmConfig->setHydratorDir($appConfig['hydrator_dir']);
        $odmConfig->setHydratorNamespace($appConfig['hydrator_ns']);
        
        $odmConfig->setDefaultDB($appConfig['dbname']);
        
        /**
         * Register file that contains annotation definitions
         * (I quess this is not needed in some later
         * versions of MongoDB)
         */
        foreach($appConfig['annotation_registry'] as $file){
            AnnotationRegistry::registerFile(
                $file
            );
        }
         
        /**
         * Configure driver
         */
        $driver = $odmConfig->newDefaultAnnotationDriver(
            array_values($appConfig['ns'])        
        );

        $odmConfig->setMetadataDriverImpl($driver);

        $this->odmConfig = $odmConfig;
    }
}