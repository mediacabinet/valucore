<?php
namespace Valu\Service\Plugin;

use Valu\Acl\Role\UniversalRole;
use Valu\Service\Plugin\AbstractPlugin;
use Valu\Service\Plugin\Auth\AclRole;

class Auth extends AbstractPlugin
{
    /**
     * Root namespace
     * 
     * @var string
     */
    const ROOT = '/';
    
    /**
     * Superuser role name
     * 
     * @var string
     */
    const ROLE_SUPERUSER = 'superuser';
    
    /**
     * Roles associated with current user
     * 
     * @var array
     */
    private static $roles = null;
    
    /**
     * Full identity information
     * 
     * @var array
     */
    private static $identity = null;
    
    /**
     * Group IDs
     * 
     * @var array
     */
    private static $groups = null;
    
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
        
        $aclRole = new AclRole($role, $this);
        return $aclRole;
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
                return (is_array($roles[$ns]) ? array_shift($roles[$ns]) : $roles[$ns]);
            }
        
            // remove last empty item (trailing slash)
            array_pop($path);
            $ns = implode('/', $path);
        
            if (!$ns) {
                $ns = '/';
            }
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
        self::$roles = $this->getIdentity('roles');
        
        if (!self::$roles) {
            self::$roles = $this->getServiceBroker()
                ->service('Acl.Role')->find($this->getId(), '/*');
        }
    
        return self::$roles;
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
     * Retrieve all user's group memberships
     * 
     * @return array
     */
    public function getGroups()
    {
        if (self::$groups === null) {
            self::$groups = $this->getIdentity('groups');
            
            if (!self::$groups) {
                $service = $this->getServiceBroker()->service('Group');
                
                $enabled = $service->disableFilter('access');
                
                try {
                    self::$groups = $service->getMemberships($this->getId());
                } catch(\Exception $e) {
                    return array();
                }
                
                if ($enabled) {
                    $service->enableFilter('access');
                }
            }
        }
        
        return self::$groups;
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
        // Assume that identity doesn't change in the middle of process
        if (!self::$identity) {
            self::$identity = $this->auth()
                ->until('is_array')->exec('getIdentity')->last();
        }
        
        if(!is_array(self::$identity)){
            throw new \Exception('User identity not found');
        }

        if($spec !== null){
            return isset(self::$identity[$spec])
                ? self::$identity[$spec]
                : null;
        }
        else{
            return self::$identity;
        }

    }
    
    /**
     * Test whether current user is a superuser
     */
    public function isSuperuser()
    {
        return $this->getAclRole() == self::ROLE_SUPERUSER;
    }
    
    public function reset()
    {
        self::$groups   = null;
        self::$identity = null;
        self::$roles    = null;
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
}