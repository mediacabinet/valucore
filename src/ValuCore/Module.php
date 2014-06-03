<?php
namespace ValuCore;

use Zend\ModuleManager\Feature;

class Module
    implements Feature\ConfigProviderInterface
{
    /**
     * getConfig implementation for ConfigListener
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }
}