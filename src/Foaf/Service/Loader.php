<?php
namespace Foaf\Service;

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
	
	public function registerServices(array $services)
	{
		foreach($services as $name => $impl){
		    
			$type 		= isset($impl['type']) ? $impl['type'] : $name;
			$class 		= isset($impl['class']) ? $impl['class'] : null;
			$factory 	= isset($impl['factory']) ? $impl['factory'] : null;
			$options 	= isset($impl['options']) ? $impl['options'] : null;
			$priority 	= isset($impl['priority']) ? $impl['priority'] : 1;

			if(is_null($options) && isset($impl['config'])){
			    $options = $impl['config'];
			}
			
			if(!$type){
				throw new \InvalidArgumentException('Service type is not defined for service: '.$name);
			}
			
			$this->registerService($name, $type, $class, $options, $priority);
			
			if($factory){
			    $this->setServiceFactory($name, $factory);
			}
		}
		
		return $this;
	}
	
	public function registerService($name, $type, $class = null, $options = array(), $priority = 1)
	{

		$this->services[$name] = array(
			'name' => $name, 
			'type' => $this->normalizeService($type),
			'options' => $options,
			'priority' => $priority
		);
		
		if($class !== null){
    		$this->getPluginManager()->setInvokableClass(
    			$name, 
    			$class
    		);
		}
		
		return $this;
	}
	
	public function setServiceFactory($name, $factory)
	{
	    $this->getPluginManager()->setFactory($name, $factory);
	    return $this;
	}
	
	/**
	 * Loads specific service
	 * 
	 * @param string $name Service name
	 * @param array $options Options to apply when first initialized
	 * @return ServiceInterface
	 */
	public function load($name, $options = null)
	{
	    if(!isset($this->services[$name])){
	        throw new Exception\ServiceNotFoundException(
                sprintf('Service by name "%s" does not exist', $name)
            );
	    }
	    
	    try{
	        /**
	         * Load pre-configured options
	         */
	        if($options == null){
	            $options = $this->services[$name]['options'];
	        }
	        
	        $service = $this->getPluginManager()->get($name, $options);
	        return $service;
	    }
	    catch(\Zend\Loader\Exception\RuntimeException $e){
	        throw new Exception\InvalidServiceException(
	            sprintf('Service implementation "%s" is not a valid. Maybe the class doesn\'t implement ServiceInterface interface.', $name)
	        );
	    }
	}

	/**
	 * Test if a service exists
	 * 
	 * @param string $service
	 */
	public function exists($service)
	{
	    $service = $this->normalizeService($service);
	    
	    foreach ($this->services as $specs){
	        if($specs['type'] == $service){
	            return true;
	        }
	    }
	    
		return false;
	}
	
	/**
	 * Load queue of service implementations
	 * 
	 * @param string $type
	 * @return \SplObjectStorage
	 */
	public function loadImplementations($service){
		
		$storage = new \SplObjectStorage();
		$service = $this->normalizeService($service);
		
		foreach($this->services as $specs){
		    
			if($specs['type'] == $service){

			    $storage->attach(
		            $this->load(
					    $specs['name'],
					    $specs['options']
				    ), 
		            $specs['priority']
			    );
			}
		}
		
		return $storage;
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
	    if($this->cache['adapter']['name']){
	        return StorageFactory::factory($this->cache);
	    }
	    else{
	        return null;
	    }
	}
	
    public final function normalizeService($service){
	    return strtolower($service);
	}
}