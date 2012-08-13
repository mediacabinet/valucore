<?php
namespace Foaf\Service\Plugin;

use Foaf\Service\Plugin\AbstractPlugin;

class Auth extends AbstractPlugin
{
    
    public function getAclRole()
    {
        return $this->getIdentity('acl_role');
    }
    
    public function getUsername()
    {
        return $this->getIdentity('username');
    }
    
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
        $identity = $this->auth()->until('is_array')->getIdentity();
        
        if(!$identity)
        {
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