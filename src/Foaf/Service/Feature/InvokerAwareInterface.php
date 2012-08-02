<?php
namespace Foaf\Service\Feature;

use Foaf\Service\Invoker\InvokerInterface;

interface InvokerAwareInterface
{
    public function setInvoker(InvokerInterface $invoker);
}