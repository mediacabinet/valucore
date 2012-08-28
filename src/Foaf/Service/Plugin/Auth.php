<?php
namespace Foaf\Service\Plugin;

use Foaf\Service\Plugin\AbstractPlugin;

class Auth extends AbstractPlugin
{
    
    const GLOBAL_CONTEXT = 'global';
    
    /**
     * Retrieve ACL role by context for authenticated user
     * 
     * If no role for given context exists, the
     * global ACL role is returned.
     * 
     * @param string $context
     * @return string
     */
    public function getAclRole($context = null)
    {
        if($context === null){
            $context = self::GLOBAL_CONTEXT;
        }
        
        $roles = $this->getAclRoles();
        $roleByContext = isset($roles[$context])
            ? $roles[$context] : null;
        
        // Fall back to global role
        if(!$roleByContext && $context !== self::GLOBAL_CONTEXT){
            $roleByContext = $this->getAclRole(self::GLOBAL_CONTEXT);
        }
        
        return $roleByContext;
    }
    
    /**
     * Retrieve authenticated user's ACL roles
     * 
     * @return array
     */
    public function getAclRoles()
    {
        return $this->getIdentity('acl_roles');
    }
    
    /**
     * Retrieve authenticated user's username
     * 
     * @return string
     */
    public function getUsername()
    {
        return $this->getIdentity('username');
    }
    
    /**
     * Retrieve authenticated user's UUID
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->getIdentity('uuid');
    }
    
    /**
     * Retrieve user's identity information
     * 
     * @param string $spec
     * @return mixed
     */
    public function getIdentity($spec = null)
    {
        $identity = $this->auth()->until('is_array')->exec('getIdentity')->last();
        
        if(!$identity){
            throw new \Exception('User identity not found');
        }
        
        if($spec !== null){
            return isset($identity[$spec])
                ? $identity[$spec]
                : null;
        }
        else{
            return $identity;
        }

    }

    /**
     * Provides direct acces to service worker configured
     * for Auth service
     *
     * @return \Foaf\Service\Broker\Worker
     */
    public function auth(){
        return $this->getServiceBroker()
            ->service('Auth');
    }
    
    /**
     * Direct access to Auth service
     * 
     * @param string $method Auth service operation
     * @param array $params Operation parameters
     */
    public function __call($method, array $params)
    {
        return $this->auth()->exec($method, $params)->first();
    }
}