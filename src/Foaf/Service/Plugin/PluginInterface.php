<?php
namespace Foaf\Service\Plugin;

use Foaf\Service\ServiceInterface;

interface PluginInterface
{
    /**
     * Set service
     * 
     * @param ServiceInterface $service
     */
    public function setService(ServiceInterface $service);
}