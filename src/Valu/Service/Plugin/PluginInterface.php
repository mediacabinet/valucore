<?php
namespace Valu\Service\Plugin;

use Valu\Service\ServiceInterface;

interface PluginInterface
{
    /**
     * Set service
     * 
     * @param ServiceInterface $service
     */
    public function setService(ServiceInterface $service);
}