<?php
namespace Foaf\Acl;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Acl 
    extends \Zend\Permissions\Acl\Acl 
    implements  ServiceLocatorAwareInterface
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
    
    public function __sleep()
    {
        $vars = get_class_vars(get_class($this));
        unset($vars['serviceLocator']);
        
        return array_keys($vars);
    }

}