<?php
namespace Foaf\Acl\Container;

use Foaf\Acl\Container\Exception\InvalidFactoryException;
use Zend\ServiceManager\AbstractPluginManager;

class FactoryManager extends AbstractPluginManager
{

    protected $allowOverride = false;

    public function validatePlugin($plugin)
    {
        if ($plugin instanceof FactoryInterface) {
            return;
        }

        throw new InvalidFactoryException(sprintf(
            'Factory of type %s is invalid; must implement FactoryInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin))
        ));
    }
}