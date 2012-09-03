<?php
namespace Foaf\Service;

use Zend\EventManager\EventManager;

use	SplObjectStorage;
use	Foaf\Service\Exception;
use	Foaf\Service\Feature;
use	Foaf\Service\ServiceInterface;
use Foaf\Service\Invoker\DefinitionBased;
use	Zend\Loader\PluginClassLoader;
use	Zend\ServiceManager\ServiceLocatorInterface;
use	Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Cache\StorageFactory;

class Loader{
	
	/**
	 * Registered service
	 * 
	 * @var PriorityQueue
	 */
	private $services;
	
	/**
	 * Array that maps service IDs to
	 * corresponding service names
	 * 
	 * @var array
	 */
	private $serviceNames;
	
	/**
	 * Contains un-attached service IDs
	 * 
	 * @var array
	 */
	private $unAttached;
	
	/**
	 * Plugin manager
	 * 
	 * @var \Foaf\Service\ServiceManager
	 */
	private $pluginManager = null;

	/**
	 * Invoker
	 * 
	 * @var InvokerInterface
	 */
	private $invoker;
	
	/**
	 * Cache settings
	 *
	 * @var array
	 */
	private $cache = array(
        'adapter' => '',
        'plugins' => array('serializer')
	);
	
	public function __construct($options = null)
	{
		$this->services = array();
		$this->serviceNames = array();
		
		if(null !== $options){
			$this->setOptions($options);
		}
	}
	
	public function setOptions($options)
	{
		if (!is_array($options) && !$options instanceof \Traversable) {
			throw new \InvalidArgumentException(sprintf(
				'Expected an array or Traversable; received "%s"',
				(is_object($options) ? get_class($options) : gettype($options))
			));
		}
		
		foreach ($options as $key => $value){
			
			$key = strtolower($key);
			
			if($key == 'services'){
				$this->registerServices($value);
			}
			else if($key == 'locator' || $key == 'service_locator'){
			    $this->setServiceLocator($value);
			}
			else if($key == 'cache_adapter'){
			
			    if(is_string($value)){
			        $value = array(
			                'name' => $value,
			                'options' => array()
			        );
			    }
			    else if(!isset($value['name'])){
			        throw new \InvalidArgumentException('Cache adapter name is missing');
			    }
			    else if(!isset($value['options'])){
			        $value['options'] = array();
			    }
			
			    $this->setCacheAdapter($value['name'], $value['options']);
			}
		}
		
		return $this;
	}
	
	public function setServiceLocator(ServiceLocatorInterface $locator)
	{
	    $this->getPluginManager()->setServiceLocator($locator);
	    
	    return $this;
	}
	
	public function getServiceLocator()
	{
	    return $this->getPluginManager()->getServiceLocator();
	}
	
	public function getPluginManager()
	{
	    if($this->pluginManager === null){
	        $this->pluginManager = new ServiceManager();
	        
	        $self = $this;
	         
	        $this->pluginManager->addInitializer(function ($instance, $pluginManager) use ($self) {
	            
	        	$name     = $pluginManager->getCreationInstanceName();
	        	$options  = $pluginManager->getCreationInstanceOptions();
	        	 
	        	/**
	        	 * Configure service
	        	*/
	        	if( $options !== null && sizeof($options) &&
	        		$instance instanceof Feature\ConfigurableInterface){
	        	    
	        		$instance->setConfig($options);
	        	}
	        
	        	/**
	        	 * Provide shared invoker instance
	        	 */
	        	if( $instance instanceof Feature\InvokerAwareInterface &&
	        			$instance instanceof Feature\DefinitionProviderInterface){
	        		 
	        		$instance->setInvoker($self->getInvoker());
	        	}
	        });
	    }
	    
	    return $this->pluginManager; 
	}
	
	/**
	 * Set cache adapter settings
	 *
	 * @param string $name
	 * @param array $options
	 */
	public function setCacheAdapter($name, array $options = array())
	{
	    $this->cache['adapter']['name'] = $name;
	    $this->cache['adapter']['options'] = $options;
	}
	
	/**
	 * Batch register services
	 * 
	 * @param array $services
	 * @throws \InvalidArgumentException
	 * @return \Foaf\Service\Loader
	 */
	public function registerServices(array $services)
	{
		foreach($services as $key => $impl){
		    
			$id 		= isset($impl['id']) ? $impl['id'] : $key;
			$name 	    = isset($impl['name']) ? $impl['name'] : null;
			$class 		= isset($impl['class']) ? $impl['class'] : null;
			$factory 	= isset($impl['factory']) ? $impl['factory'] : null;
			$options 	= isset($impl['options']) ? $impl['options'] : null;
			$priority 	= isset($impl['priority']) ? $impl['priority'] : 1;

			if(is_null($options) && isset($impl['config'])){
			    $options = $impl['config'];
			}
			
			if(!$name){
				throw new \InvalidArgumentException('Service name is not defined for service: '.$id);
			}
			
			$this->registerService($id, $name, $class, $options, $priority);
			
			if($factory){
			    $this->setServiceFactory($id, $factory);
			}
		}
		
		return $this;
	}
	
	/**
	 * Register service
	 * 
	 * @param string $id Unique service ID
	 * @param string $name Service name
	 * @param string $class Invokable service class name
	 * @param array $options
	 * @param int $priority
	 * @return \Foaf\Service\Loader
	 */
	public function registerService($id, $name, $class = null, $options = array(), $priority = 1)
	{

	    $name = $this->normalizeService($name);
	    
	    if(!isset($this->services[$name])){
	        $this->services[$name] = array();
	    }
	    
		$this->services[$name][$id] = array(
			'options' => $options,
			'priority' => $priority
		);
		
		$this->serviceNames[$id] = $name;
		
		// Mark service un-attached
		if(!isset($this->unAttached[$name])){
		    $this->unAttached[$name] = array();
		}
		
		$this->unAttached[$name][] = $id;
		
		// Register as invokable
		if($class !== null){
    		$this->getPluginManager()->setInvokableClass(
    			$id, 
    			$class
    		);
		}
		
		return $this;
	}
	
	/**
	 * Define service factory class name for service ID
	 * 
	 * @param string $id
	 * @param string $factory
	 * @return \Foaf\Service\Loader
	 */
	public function setServiceFactory($id, $factory)
	{
	    $this->getPluginManager()->setFactory($id, $factory);
	    return $this;
	}
	
	/**
	 * Loads specific service by ID
	 * 
	 * @param string $name Service name
	 * @param array $options Options to apply when first initialized
	 * @return ServiceInterface
	 */
	public function load($id, $options = null)
	{
	    $name = isset($this->serviceNames[$id])
	        ? $this->serviceNames[$id]
	        : null;
	    
	    if(!$name){
	        throw new Exception\ServiceNotFoundException(
                sprintf('Service ID "%s" does not exist', $id)
            );
	    }
	    
	    try{
	        /**
	         * Load pre-configured options
	         */
	        if($options == null){
	            $options = $this->services[$name][$id]['options'];
	        }
	        
	        $instance = $this->getPluginManager()->get($id, $options);
	        return $instance;
	    }
	    catch(\Zend\Loader\Exception\RuntimeException $e){
	        throw new Exception\InvalidServiceException(
	            sprintf('Service implementation "%s" is not a valid. Maybe the class doesn\'t implement ServiceInterface interface.', $id)
	        );
	    }
	}

	/**
	 * Test if a service exists
	 * 
	 * @param string $name
	 */
	public function exists($name)
	{
	    $name = $this->normalizeService($name);
	    
	    return  isset($this->services[$name]) && 
	            sizeof($this->services[$name]);
	}

	/**
	 * Attach listeners to event manager by service name
	 * 
	 * This method should not be called outside Broker.
	 * 
	 * @param EventManager $eventManager
	 * @param string $name Name of the service
	 */
	public function attachListeners(EventManager $eventManager, $name)
	{
	    $normalName = $this->normalizeService($name);
	    
	    if( !isset($this->services[$normalName]) || 
            !sizeof($this->services[$normalName]) ||
	        !sizeof($this->unAttached[$normalName])){
	        
	        return;
	    }
	    
	    // Attach all
	    foreach($this->unAttached[$normalName] as $id){
            $eventManager->attach(
                $name, 
                $this->load($id), 
                $this->services[$normalName][$id]['priority']
            );
	    }
	    
	    $this->unAttached[$normalName] = array();
	}
	
	/**
	 * Get definition based invoker
	 * 
	 * @return InvokerInterface
	 */
	protected function getInvoker()
	{
	    if($this->invoker == null){
	        $this->invoker = new DefinitionBased(
	            $this->getCacheAdapter()        
            );
	    }
	    
	    return $this->invoker;
	}
	
	/**
	 * Get cache adapter
	 *
	 * @return \Zend\Cache\Storage\Adapter\AdapterInterface
	 */
	protected function getCacheAdapter()
	{
	    if(isset($this->cache['adapter']['name'])){
	        return StorageFactory::factory($this->cache);
	    }
	    else{
	        return null;
	    }
	}
	
    public final function normalizeService($name){
	    return strtolower($name);
	}
}