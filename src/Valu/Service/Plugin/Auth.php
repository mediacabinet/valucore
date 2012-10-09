<?php
namespace Valu\Service\Plugin;

use Valu\Acl\Role\UniversalRole;
use Valu\Service\Plugin\AbstractPlugin;

class Auth extends AbstractPlugin
{
    /**
     * Root namespace
     * 
     * @var string
     */
    const ROOT = '/';
    
    private $id = null;
    
    private $roles = null;
    
    /**
     * Retrieve universal role for current user in given ACL
     * namespace
     * 
     * @param string $ns
     * @return \Valu\Acl\Role\UniversalRole
     */
    public function getUniversalAclRole($ns = self::ROOT)
    {
        $role = $this->getAclRole($ns);
        
        $universalRole = new UniversalRole($role, $this->getId());
        return $universalRole;
    }
    
    /**
     * Retrieve ACL role by context for authenticated user
     * 
     * If no role for given context exists, the
     * global ACL role is returned.
     * 
     * @param string $ns
     * @return string|null
     */
    public function getAclRole($ns = self::ROOT)
    {

        $roles = $this->getAclRoles();
        $path  = explode('/', $ns);
        
        while(sizeof($path)){
            if (isset($roles[$ns])) {
                return array_shift($roles[$ns]);   
            }

            array_pop($path); // remove last empty item (trailing slash)
            if(sizeof($path)) 
                array_pop($path); // remove last item
    
            $ns = implode('/', $path);
        }
        
        return null;
    }
    
    /**
     * Retrieve authenticated user's ACL roles
     * 
     * @return array
     */
    public function getAclRoles()
    {
        if (!$this->roles) {
            $this->roles = $this->getServiceBroker()
                ->service('Acl.Role')->find($this->getId(), '/*');
        }
        
        return $this->roles;
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
     * Retrieve account for current authentication
     * session
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->getIdentity('account');
    }
    
    /**
     * Retrieve authenticated user's ID
     *
     * @return string
     */
    public function getId()
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
        $identity = $this->auth()->until('is_array')->exec('getIdentity')->last();
        
        if (isset($identity['id']) && $identity['id'] !== $this->id) {
            $this->reset();
            $this->id = $identity['id'];
        }
        
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
     * @return \Valu\Service\Broker\Worker
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
    
    private function reset()
    {
        $this->roles = null;
    }
}