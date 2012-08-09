<?php
namespace Foaf\Service\Feature;

use Foaf\Service\Broker;

interface ServiceBrokerAwareInterface
{
    public function setServiceBroker(Broker $serviceBroker);
}