<?php
namespace Valu\InputFilter\Configurator;

use Valu\InputFilter\Configurator\Delegate\DelegateInterface;
use Zend\ServiceManager\AbstractPluginManager;

class DelegateManager extends AbstractPluginManager
{
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof DelegateInterface) {
            // we're okay
            return;
        }
        
        throw new \RuntimeException(sprintf(
            'Delegate plugin of type %s is invalid; must implement %s\Delegate\DelegateInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            __NAMESPACE__
        ));
    }
}