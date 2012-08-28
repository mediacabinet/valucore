<?php
namespace Foaf\Acl;

use Zend\Permissions\Acl\Acl as ZendAcl;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Acl extends ZendAcl implements ServiceLocatorAwareInterface
{

    /**
     * Service locator interface
     * 
     * @var \Zend\ServiceManager\ServiceLocatorAwareInterface
     */
    protected $serviceLocator;
    
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}