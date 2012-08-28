<?php
namespace Foaf\Service\Plugin;

use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;
use Foaf\Service\Plugin\AbstractPlugin;
use Foaf\Service\Exception;

class Acl extends AbstractPlugin
{
    
    /**
     * Assert that role has access to resource, if not throw an exception
     * 
     * @param string|RoleInterface $role
     * @param string|ResourceInterface $resource
     * @param string|array $privilege
     * @param string $messageTemplate
     * @param array $messageParams
     * @throws \InvalidArgumentException
     * @throws Exception\PermissionDeniedException
     */
    public function assertAllow($role, $resource, $privilege, $messageTemplate = '', $messageParams = array())
    {

        if(!is_string($role) && !($role instanceof RoleInterface)){
            throw new \InvalidArgumentException('Invalid role, string or instanceof RoleInterface expected');
        }
        
        if(!is_string($resource) && !($resource instanceof ResourceInterface)){
            throw new \InvalidArgumentException('Invalid resource, string or instanceof ResourceInterface expected');
        }
        
        if(!$this->acl()->isAllowed($role, $resource, $privilege)){
            
            if(!$messageTemplate){
                $roleId = is_string($role) ? $role : $role->getRoleId();
                $resourceId = is_string($resource) ? $resource : $resource->getResourceId();
            
                $messageParams = array(
                        'ROLE'     => $roleId,
                        'RESOURCE' => $resourceId,
                        'OPERATION' => $this->getService()->getEvent()->getOperation()
                );
            
                $messageTemplate = 'Role "%ROLE%" does not have enough privileges to perform operation "%OPERATION%" for resource "%RESOURCE%"';
            }
            
            throw new Exception\PermissionDeniedException(
                $messageTemplate,
                $messageParams
            );
        }
    }
    
    /**
     * Provides direct acces to service worker configured
     * for Acl service
     * 
     * @return \Foaf\Service\Broker\Worker
     */
    public function acl(){
        return $this->getServiceBroker()
            ->service('Acl');
    }
    
    /**
     * Direct access to Acl service
     * 
     * @param string $method Acl service operation
     * @param array $params Operation parameters
     */
    public function __call($method, array $params)
    {
        return $this->acl()->exec($method, $params)->first();
    }
}