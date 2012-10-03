<?php
namespace Valu\Doctrine\ServiceManager;

use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ZendDataCache;
use Valu\Service\Broker;
use Valu\Service\Loader;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration as MongoDbConfig;
use Doctrine\Common\Annotations\AnnotationRegistry;

class DocumentManagerFactory implements FactoryInterface
{
    
    /**
     * ODM connection
     * 
     * @var \Doctrine\MongoDB\Connection
     */
    private static $connection;
    
    /**
     * ODM configuration
     * 
     * @var \Doctrine\ODM\MongoDB\Configuration
     */
    private static $config;
    
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
		$dm = \Doctrine\ODM\MongoDB\DocumentManager::create(
	        self::getConnection($serviceLocator), 
	        self::getConfig($serviceLocator)
        );
		
		return $dm;
    }
    
    /**
     * Retrieve static ODM configuration instance
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Doctrine\ODM\MongoDB\Configuration
     */
    protected static function getConfig(ServiceLocatorInterface $serviceLocator){
        
        if(self::$config){
            return self::$config;
        }
        
        $config = $serviceLocator->get('Configuration');
        
        /**
         * Configure MongoDB proxy and hydrator
         */
        $appConfig = $config['doctrine']['mongodb'];
        
        $odmConfig = new MongoDbConfig;
        $odmConfig->setProxyDir($appConfig['proxy_dir']);
        $odmConfig->setProxyNamespace($appConfig['proxy_ns']);
        
        $odmConfig->setHydratorDir($appConfig['hydrator_dir']);
        $odmConfig->setHydratorNamespace($appConfig['hydrator_ns']);
        
        if(!isset($appConfig['dbname']) && isset($config['mongodb']['database'])){
            $appConfig['dbname'] = $config['mongodb']['database'];
        }
        
        if (isset($appConfig['dbname'])) {
            $odmConfig->setDefaultDB($appConfig['dbname']);
        }
        
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
        
        if (isset($appConfig['cache']) && isset($appConfig['cache']['adapter'])) {
            $cache = null;
            
            switch ($appConfig['cache']['adapter']) {
                case 'zendservershm':
                    $cache = new ZendDataCache();
                    break;
                case 'apc':
                    $cache = new ApcCache();
                    break;
                case 'file':
                    $cache = new PhpFileCache($appConfig['cache']['directory']);
                    break;
            }
            
            if ($cache) {
                $odmConfig->setMetadataCacheImpl($cache);
            }
        }
        
        self::$config = $odmConfig;
        return self::$config;
    }
    
    /**
     * Retrieve static database connection instance
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Doctrine\MongoDB\Connection
     */
    protected static function getConnection(ServiceLocatorInterface $serviceLocator)
    {
        if (self::$connection) {
            return self::$connection;
        }
        
        $mongo = $serviceLocator->get('Mongo');
        
        self::$connection = new Connection(
            $mongo
        );
        
        return self::$connection;
    }
}