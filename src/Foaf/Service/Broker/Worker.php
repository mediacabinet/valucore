<?php
namespace Foaf\Service\Broker;

use Foaf\Service\Broker;

/**
 * Worker separates certain Unit Of Work from others
 * when executing a service operation
 * 
 * @author Juha Suni
 */
class Worker{
	
	/**
	 * Service broker
	 * 
	 * @var \Foaf\Service\Broker
	 */
	protected $broker;
	
	/**
	 * Service name
	 * 
	 * @var string
	 */
	protected $service;
	
	/**
	 * Callback 
	 * 
	 * @var callback
	 */
	protected $until;
	
	/**
	 * Registered service arguments
	 * 
	 * @var array|null
	 */
	protected $args = null;
	
	/**
	 * Service context
	 * 
	 * @var string
	 */
	protected $context = null;
	
	public function __construct(Broker $broker, $service)
	{
		$this->broker 	= $broker;
		$this->service 	= $service;
	}
	
	/**
	 * Set service context
	 * 
	 * @param string $context
	 */
	public function context($context){
	    $this->context = $context;
	    return $this;
	}
	
	/**
	 * Set operation as delayed
	 * 
	 * @return \Foaf\Service\Broker\Worker
	 */
	public function delay($operation, $args = null)
	{
	    $args = ($args) ?:$this->args;
	    return $this->broker->delay($this->service, $operation, $args);
	}
	
	/**
	 * Execute operation as soon as possible
	 * in separate process
	 */
	public function fork($operation, $args = null){
	    $args = ($args) ?:$this->args;
	    return $this->broker->fork($this->service, $operation, $args);
	}
	
	/**
	 * Set a callback function to execute on
	 * each service implementation in service stack
	 * 
	 * When callback function returns true, the service
	 * event is stopped and the next service in stack won't be 
	 * processed.
	 * 
	 * @param callback $callback Valid callback function
	 * @return \Foaf\Service\Broker\Worker
	 */
	public function until($callback)
	{
		$this->until = $callback;
		return $this;
	}
	
	/**
	 * Set the args for the operation
	 * 
	 * @param array $args
	 * @return \Foaf\Service\Broker\Worker
	 */
	public function args($args)
	{
		$this->args = $args;
		return $this;
	}
	
	/**
	 * Execute operation for all service implementations
	 * 
	 * @param string $operation
	 * @param array|null $args
	 * @return \Zend\EventManager\ResponseCollection
	 */
	public function exec($operation, $args = null)
	{
		$args = ($args) ?:$this->args;
		
		if($this->context){
    		if($this->until){
    			return $this->broker->executeInContextUntil($this->context, $this->service, $operation, $args, $this->until);
    		}
    		else{
    			return $this->broker->executeInContext($this->context, $this->service, $operation, $args);
    		}
		}
		else{
		    if($this->until){
		        return $this->broker->executeUntil($this->service, $operation, $args, $this->until);
		    }
		    else{
		        return $this->broker->execute($this->service, $operation, $args);
		    }
		}
	}
	
	public function __call($method, $args)
	{
	    return 	$this->exec($method, $args)
	    		->first();
	}
	
}