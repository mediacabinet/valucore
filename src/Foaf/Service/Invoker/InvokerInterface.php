<?php
namespace Foaf\Service\Invoker;

use Foaf\Service\ServiceInterface;
use Foaf\Service\ServiceEvent;

interface InvokerInterface
{
    public function invoke(ServiceInterface $service, ServiceEvent $e);
}