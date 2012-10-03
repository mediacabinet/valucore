<?php
namespace Valu\Service\Invoker;

use Valu\Service\ServiceInterface;
use Valu\Service\ServiceEvent;

interface InvokerInterface
{
    public function invoke(ServiceInterface $service, ServiceEvent $e);
}