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
    
    public function getUserId()
    {
        return $this->getIdentity('id');
    }
    
    /**
     * Retrieve user's identity information
     * 
     * @param string $spec
     * @return mixed
     */
    public function getIdentity($spec = null)
    {
        if(!$this->auth()->hasIdentity()){
            throw new \Exception('User identity not found');
        }
        
        $identity = $this->auth()->getIdentity();
        
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