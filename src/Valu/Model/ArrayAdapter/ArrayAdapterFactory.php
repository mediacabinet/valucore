<?php
namespace Valu\Model\ArrayAdapter;

use Valu\Model\ArrayAdapter;
use Zend\Cache\StorageFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ArrayAdapterFactory implements FactoryInterface
{
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
    private $cache;
    
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = new ArrayAdapter();
        
        $adapter->getEventManager()->attach('extract', $this->getModelListener($serviceLocator));
        $adapter->getEventManager()->attach('extract', $this->getDateFormatterListener($serviceLocator));

        $cache = $this->getCache($serviceLocator);
        
        if ($cache) {
            $adapter->setCache($cache);
        }
        
        return $adapter;
    }

    /**
     * Fetch initialized ModelListener
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Valu\Model\ArrayAdapter\ModelListener
     */
    private function getModelListener(ServiceLocatorInterface $serviceLocator)
    {
        if (!$this->modelListener) {
            $this->modelListener = new ModelListener(
                $this->getOptions($serviceLocator, 'model_listener'));
        }
    
        return $this->modelListener;
    }
    
    /**
     * Fetch initialized DateFormatterListener
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Valu\Model\ArrayAdapter\DateFormatterListener
     */
    private function getDateFormatterListener(ServiceLocatorInterface $serviceLocator)
    {
        if (!$this->dateFormatterListener) {
            $this->dateFormatterListener = new DateFormatterListener(
                $this->getOptions($serviceLocator, 'date_formatter'));
        }
        
        return $this->dateFormatterListener;
    }
    
    /**
     * Fetch cache instance
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Zend\Cache\Storage\StorageInterface
     */
    private function getCache(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Configuration');
        
        $adapterConfig = isset($config['model_framework']['array_adapter'])
            ? $config['model_framework']['array_adapter'] : null;
        
        $cache = null;
        
        if(isset($adapterConfig['cache'])){
            $cache = StorageFactory::factory($adapterConfig['cache']);
        } elseif($serviceLocator->has('ObjectCache')) {
            $cache = $serviceLocator->get('ObjectCache');
        }
        
        return $cache;
    }
    
    /**
     * Retrieve options for listener
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $key
     * @return array
     */
    private function getOptions(ServiceLocatorInterface $serviceLocator, $key)
    {
        $config = $serviceLocator->get('Config');
        
        if (isset($config['array_adapter'][$key])) {
            return $config['array_adapter'][$key];
        } else {
            return array();
        }
    }
}