<?php
namespace Valu\Service;

use Zend\EventManager\ResponseCollection;
use Zend\EventManager\Event;

class ServiceEvent extends Event
{

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
     * Service context
     * 
     * @var string
     */
    protected $context;

    /**
     * Exception
     *
     * @var \Exception
     */
    protected $exception;
    
    /**
     * Response collection
     * @var ResponseCollection
     */
    protected $responses;

    /**
     * Retrieve the name of the service
     * 
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set the name of the service
     * 
     * @param string
     * @return ServiceEvent
     */
    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }
    
    /**
     * Retrieve the name of the operation
     * 
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set the name of the operation
     * 
     * @param string
     * @return ServiceEvent      
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
        return $this;
    }

    /**
     * Retrieve current exception, if any
     * 
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Set current exception
     * 
     * @param \Exception $exception
     * @return ServiceEvent
     */
    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
        return $this;
    }

    /**
     * Retrieve current service context
     * 
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set current service context
     * 
     * @param unknown_type $context
     * @return ServiceEvent
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }
    
	/**
     * @return \Zend\EventManager\ResponseCollection
     */
    public function getResponses()
    {
        return $this->responses;
    }

	/**
     * @param \Zend\EventManager\ResponseCollection $responses
     */
    public function setResponses($responses)
    {
        $this->responses = $responses;
    }

}