<?php
namespace Valu\Service;

use Zend\EventManager\Event;

class ServiceEvent extends Event{
    
    /**
     * Service service
     * 
     * @var string
     */
    protected $service;
    
    /**
     * Operation
     * 
     * @var string
     */
    protected $operation;
    
	/**
	 * @return string
	 */
	public function getService() {
		return $this->service;
	}

	/**
	 * @return string
	 */
	public function getOperation() {
		return $this->operation;
	}

	/**
	 * @param string servicee
	 */
	public function setService($service) {
		$this->service = $service;
	}

	/**
	 * @param string $operation
	 */
	public function setOperation($operation) {
		$this->operation = $operation;
	}
	
	public function getContext(){
	    return $this->getTarget();
	}
	
	public function setContext($context){
	    return $this->setTarget($context);
	}
}