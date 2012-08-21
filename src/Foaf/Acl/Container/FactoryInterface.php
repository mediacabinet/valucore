<?php
namespace Foaf\Acl\Container;

interface FactoryInterface
{
    /**
     * Create new ACL instance
     * 
     * @return \Zend\Permissions\Acl\Acl
     */
    public function createAcl();
}