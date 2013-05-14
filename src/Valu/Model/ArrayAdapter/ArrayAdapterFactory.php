<?php
namespace Valu\Model\ArrayAdapter;

use Valu\Model\ArrayAdapter\DateFormatterDelegate;
use Valu\Model\ArrayAdapter\RecursionDelegate;
use Valu\Model\ArrayAdapter\ObjectRecursionDelegate;
use Valu\Model\ArrayAdapter;
use Zend\Cache\StorageFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ArrayAdapterFactory implements FactoryInterface
{

    /**
     * @var RecursionListener
     */
    private $recursionListener;

    /**
     * @var ModelListener
     */
    private $modelListener;
    
    /**
     * @var DateFormatterListener
     */
    private $dateFormatterListener;
    
    /**
     * Shared cache instance
     * @var unknown_type
     */
    private static $cache;
    
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = new ArrayAdapter();
        
        $adapter->getEventManager()->attach('extract', $this->getRecursionListener(), 10000);
        $adapter->getEventManager()->attach('extract', $this->getModelListener());
        $adapter->getEventManager()->attach('extract', $this->getDateFormatterListener());

        $cache = $this->getCache($serviceLocator);
        
        if ($cache) {
            $adapter->setCache($cache);
        }
        
        return $adapter;
    }
    
    private function getRecursionListener()
    {
        if (!$this->recursionListener) {
            $this->recursionListener = new RecursionListener();
        }
        
        return $this->recursionListener;
    }
    
    private function getModelListener()
    {
        if (!$this->modelListener) {
            $this->modelListener = new ModelListener();
        }
    
        return $this->modelListener;
    }
    
    private function getDateFormatterListener()
    {
        if (!$this->dateFormatterListener) {
            $this->dateFormatterListener = new DateFormatterListener();
        }
        
        return $this->dateFormatterListener;
    }
    
    private function getCache(ServiceLocatorInterface $serviceLocator)
    {
        if (!self::$cache) {
            $config = $serviceLocator->get('Configuration');
            
            $adapterConfig = isset($config['model_framework']['array_adapter'])
            ? $config['model_framework']['array_adapter'] : null;
            
            $cache = null;
            
            if($adapterConfig
                    && isset($adapterConfig['cache']['enabled'])
                    && $adapterConfig['cache']['enabled']){
            
                if (isset($adapterConfig['cache']['adapter'])) {
                    unset($adapterConfig['cache']['enabled']);
            
                    $cache = StorageFactory::factory($adapterConfig['cache']);
                } else {
                    $cache = $serviceLocator->get('Cache');
                }
            }
            
            if (!$cache) {
                $cache = StorageFactory::factory(array('adapter' => 'memory'));
            }
            
            self::$cache = $cache;
        }
        
        return self::$cache;
    }
}