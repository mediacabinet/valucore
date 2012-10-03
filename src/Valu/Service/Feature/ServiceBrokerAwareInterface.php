<?php
namespace Valu\Service\Feature;

use Valu\Service\Broker;

interface ServiceBrokerAwareInterface
{
    public function setServiceBroker(Broker $serviceBroker);
}