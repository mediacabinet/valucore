<?php
namespace Foaf\Service;

use Foaf\Service\Loader;
use Foaf\Service\Feature;
use Foaf\Service\Plugin\PluginManager;
use	Foaf\Service\Broker\Worker;
use	Foaf\Service\Exception\ServiceNotFoundException;
use	Zend\EventManager\EventManager;
use	Zend\EventManager\EventManagerInterface;
use	Zend\EventManager\ResponseCollection;

class Broker{

    const CONTEXT_NATIVE = 'native';
    
    const CONTEXT_HTTP = 'http';
    
    const CONTEXT_CLI = 'cli';
    
	/**
	 * Service loader
	 * 
	 * @var \Foaf\Service\Loader
	 */
	private $loader;
	
	/**
	 * Service event manager
	 * 
	 * @var EventManager
	 */
	private $serviceEventManager;
	
	/**
	 * Common event manager
	 *
	 * @var EventManagerInterface
	 */
	private $commonEventManager;
	
	/**
	 * Array of attached services
	 * 
	 * @var array
	 */
	private $attached = array();
	
	/**
	 * Service context
	 * 
	 * @var string
	 */
	private $defaultContext;
	
	public function __construct($options = null){
	    
	    $this->setDefaultContext(self::CONTEXT_NATIVE);
		$this->serviceEventManager = new EventManager();
		
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
	        	
	        if($key == 'loader'){
	            $this->setLoader($value);
	        }
	    }
	
	    return $this;
	}
	
	/**
	 * Retrieve current service context
	 *
	 * @return string
	 */
	public function getDefaultContext(){
	    return $this->defaultContext;
	}
	
	/**
	 * Set current service context
	 * 
	 * @param string $context
	 */
	public function setDefaultContext($context){
	    $this->defaultContext = $context;
	    return $this;
	}
	
	/**
	 * Set service loader
	 * 
	 * @param Loader $loader
	 */
	public function setLoader(Loader $loader){
		$this->loader = $loader;
		
		$self = $this;
		
		$loader->getPluginManager()->addInitializer(function ($instance) use ($self) {
		    if($instance instanceof Feature\ServiceBrokerAwareInterface){
		        $instance->setServiceBroker($self);
		    }
		});
		
		return $this;
	}
	
	/**
	 * Get service loader
	 * 
	 * @return Loader
	 */
	public function getLoader()
	{
		return $this->loader;
	}

	/**
	 * Set event manager
	 * 
	 * @param EventManagerInterface $events
	 */
	public function setEventManager(EventManagerInterface $events)
	{
		$this->commonEventManager = $events;
		return $this;
	}
	
	/**
	 * Get event manager
	 * 
	 * @return EventManagerInterface
	 */
	public function getEventManager(){
		
		if(null === $this->commonEventManager){
			$this->commonEventManager = new EventManager();
		}
		
		return $this->commonEventManager;
	}
	
	/**
	 * Does a service exist?
	 * 
	 * @param string $service
	 * @return boolean
	 */
	public function exists($service){
		return $this->loader->exists($service);
	}
	
	/**
	 * Initialize and retrieve a new Service Worker
	 * 
	 * @param string $service
	 * @throws ServiceNotFoundException
	 * @return Worker
	 */
	public function service($service){
	    
	    if(!$this->exists($service)){
	        throw new ServiceNotFoundException(sprintf('Service "%s" not found', $service));
	    }
	    
		return new Worker($this, $service);
	}
	
	/**
	 * Execute service operation
	 * 
	 * @param string $service
	 * @param string $operation
	 * @param array $argv
	 * @param mixed $callback Valid PHP callback
	 */
	public function execute($service, $operation, $argv = array(), $callback = null)
	{
		return $this->exec(false, $this->getDefaultContext(), $service, $operation, $argv, $callback);
	}
	
	/**
	 * Excecute service operation in given context
	 * 
	 * @param string $context
	 * @param string $service
	 * @param string $operation
	 * @param array $argv
	 * @param mixed $callback
	 */
	public function executeInContext($context, $service, $operation, $argv = array(), $callback = null){
	    return $this->exec(false, $context, $service, $operation, $argv, $callback);
	}
	
	/**
	 * Execute service operation until callback returns false
	 * 
	 * @param string $service
	 * @param string $operation
	 * @param array $argv
	 * @param mixed $callback Valid PHP callback
	 */
	public function executeUntil($service, $operation, $argv = array(), $callback = null)
	{
		return $this->exec(true, $this->getDefaultContext(), $service, $operation, $argv, $callback);
	}
	
	/**
	 * Excecute service operation in given context until callback returns false
	 *
	 * @param string $context
	 * @param string $service
	 * @param string $operation
	 * @param array $argv
	 * @param mixed $callback
	 */
	public function executeInContextUntil($context, $service, $operation, $argv = array(), $callback = null){
	    return $this->exec(true, $context, $service, $operation, $argv, $callback);
	}
	
	/**
	 * Retrieve service queue
	 * 
	 * @return Queue
	 */
	public function queue(){
	    
	    if(!$this->queue){
	        $this->queue = new Queue($this);
	    }
	    
	    return $this->queue;
	}
	
	/**
	 * Delay execution of service operation
	 * 
	 * @param int $priority
	 * @param string $service
	 * @param string $operation
	 * @param array $args
	 */
	public function delay($priority, $service, $operation, $args = null){
		return $this->queue()->add($priority, $service, $operation, $args);
	}
	
	/**
	 * Fork execution of service operation
	 * 
	 * @param string $service
	 * @param string $operation
	 * @param array $args
	 */
	public function fork($service, $operation, $args = null){
		return $this->queue()->add(null, $service, $operation, $args);
	}
	
	/**
	 * Retrieve implementations and their priority information for service
	 * 
	 * @param string $service
	 * @return \SplObjectStorage
	 */
	public function getImplementations($service)
	{
		return $this->loader->loadImplementations($service);
	}
	
	/**
	 * Prepares events for operation
	 * 
	 * @param string $context
	 * @param string $service
	 * @param string $operation
	 * @param array $argv
	 * @return ServiceEvent
	 */
	protected function prepareEvent($context, $service, $operation, $argv)
	{
		$name	= $service . '.' . $operation;
		$storage= $this->getImplementations($service);
		$argv	= is_null($argv) ? array() : $argv;
		
		$event 	= new ServiceEvent();
		$event->setParams($argv);
		$event->setName($name);
		$event->setService($service);
		$event->setOperation($operation);
		$event->setContext($context);
		
		if(!isset($this->attached[$name])){
		    $this->attached[$name] = array();
		}

		/**
		 * Bind services that have not been bound previously
		 */
		if(sizeof($storage)){
			foreach($storage as $impl){
				if(!in_array($impl, $this->attached[$name], true)){
					$this->attached[$name][] = $impl;
	
					$this->serviceEventManager->attach(
						$name,
						$impl,
						$storage->offsetGet($impl)
					);
				}
			}
		}
	
		/**
		 * Unbind services that no longer exist in the stack
		 */
		if(sizeof($this->attached[$name])){
			foreach($this->attached[$name] as $key => $impl){
				if(!$storage->contains($impl)){
					unset($this->attached[$name][$key]);
				}
			}
		}
		
		return $event;
	}
	
	protected function exec($untilFlag, $context, $service, $operation, $argv = array(), $callback = null){
		
	    if(!$this->exists($service)){
	        throw new ServiceNotFoundException(sprintf('Service "%s" not found', $service));
	    }
	    
		$event	= $this->prepareEvent($context, $service, $operation, $argv);
		$name	= $event->getName();
		
		$eventArgs = array(
			'argv' 		=> $argv,
			'service' 	=> $service,
			'operation' => $operation
		);

		$eventResponses = $this->trigger(
			'pre.'.$name, 
			$service, 
			$eventArgs, 
			function($response){if($response === false) return true;}
		);
		
		if($eventResponses->stopped() && $eventResponses->last() === false){
			$responses = new ResponseCollection();
			$responses->push(false);
			$responses->setStopped(true);
			
			return $responses;
		}
		else if($eventResponses->count()){
			foreach($eventResponses as $response){
				if(is_array($response)){
					$argv = array_merge(
						$argv,
						$response
					);
				}
			}
		}
		
		if($untilFlag){
			$responses = $this->serviceEventManager->triggerUntil(
				$event,
				$callback
			);
		}
		else{
			$responses = $this->serviceEventManager->trigger(
				$event,
				$callback
			);
		}
		
		$eventArgs = array(
			'argv' 		=> $argv,
			'responses' => $responses 	
		);
		
		$this->trigger(
			'post.'.$name, 
			$service, 
			$eventArgs
		);
		
		return $responses;
	}
	
	protected function trigger($event, $service, array $argv, $callback = null)
	{
		return $this->getEventManager()->trigger($event, $service, $argv, $callback);
	}
}