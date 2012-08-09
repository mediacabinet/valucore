<?php
namespace Foaf\Service\Plugin;

use Foaf\Service\Broker;
use Foaf\Service\Feature;
use Foaf\Service\ServiceInterface;

abstract class AbstractPlugin 
    implements Feature\ServiceBrokerAwareInterface, PluginInterface
{
    
    /**
     * Service broker
     * 
     * @var Foaf\Service\Broker
     */
    private $serviceBroker;
    
    /**
     * (non-PHPdoc)
     * @see \Foaf\Service\Plugin\PluginInterface::setService()
     */
    public function setService(ServiceInterface $service)
    {
        $this->service = $service;
    }
    
    /**
     * Retrieve service instance
     * 
     * @return ServiceInterface
     */
    public function getService()
    {
        return $this->service;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Foaf\Service\Feature\ServiceBrokerAwareInterface::setServiceBroker()
     */
    public function setServiceBroker(Broker $serviceBroker)
    {
        $this->serviceBroker = $serviceBroker;
    }
    
    /**
     * Retrieve service broker instance
     * 
     * @return \Foaf\Service\Broker
     */
    public function getServiceBroker()
    {
        return $this->serviceBroker;
    }
}