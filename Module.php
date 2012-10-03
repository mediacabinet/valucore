<?php
namespace ValuCore;

use Zend\ModuleManager\Feature;

class Module
    implements Feature\AutoloaderProviderInterface
{
    /**
     * getAutoloaderConfig() defined by AutoloaderProvider interface.
     *
     * @see AutoloaderProvider::getAutoloaderConfig()
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    'Valu' => __DIR__ . '/src/Valu',
                ),
            ),
        );
    }
}