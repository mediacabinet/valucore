<?php
namespace Valu\Service\Plugin;

use Valu\Service\Broker;
use Valu\Service\Feature;
use Valu\Service\ServiceInterface;

abstract class AbstractPlugin 
    implements Feature\ServiceBrokerAwareInterface, PluginInterface
{
    
    /**
     * Service broker
     * 
     * @var Valu\Service\Broker
     */
    private $serviceBroker;
    
    /**
     * (non-PHPdoc)
     * @see \Valu\Service\Plugin\PluginInterface::setService()
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
     * @see \Valu\Service\Feature\ServiceBrokerAwareInterface::setServiceBroker()
     */
    public function setServiceBroker(Broker $serviceBroker)
    {
        $this->serviceBroker = $serviceBroker;
    }
    
    /**
     * Retrieve service broker instance
     * 
     * @return \Valu\Service\Broker
     */
    public function getServiceBroker()
    {
        return $this->serviceBroker;
    }
}