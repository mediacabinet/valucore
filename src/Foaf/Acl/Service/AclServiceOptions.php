<?php
namespace Foaf\Acl\Service;

class AclServiceOptions extends \Zend\Stdlib\AbstractOptions{
    
    /**
     * ACL roles
     * 
     * @param array
     */
    protected $roles = array();
    
    /**
     * ACL resources
     * 
     * @var array
     */
    protected $resources = array();
    
    /**
     * ACL allow rules
     * 
     * @var array
     */
    protected $allow = array();

    /**
     * ACL deny rules
     *
     * @var array
     */
    protected $deny = array();
    

    public function getAllow()
    {
        return $this->allow;
    }

	public function setAllow(array $allow)
    {
        $this->allow = $allow;
    }
    
    public function getDeny()
    {
        return $this->deny;
    }
    
    public function setDeny(array $deny)
    {
        $this->deny = $deny;
    }
    

	/**
     * Retrieve ACL roles
     * 
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set ACL roles
     * 
     * @param array $roles
     * @throws \InvalidArgumentException
     */
	public function setRoles(array $roles)
    {
        foreach ($roles as &$role){
            if(!is_array($role)){
                $role = array('id' => $role, 'parents' => null);
            }
            
            array_key_exists('parents', $role) || $role['parents'] = null;
            
            if(!isset($role['id'])){
                throw new \InvalidArgumentException('Invalid role definition');
            }
        }
        
        $this->roles = $roles;
    }
    
    /**
     * Retrieve ACL resources
     * 
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Set ACL resources
     * 
     * @param array $resources
     * @throws \InvalidArgumentException
     */
	public function setResources(array $resources)
    {
        foreach ($resources as &$resource){
            if(!is_array($resource)){
                $resource = array('id' => $resource, 'parent' => null);
            }
            
            array_key_exists('parent', $resource) || $role['parent'] = null;
            
            if(!isset($resource['id'])){
                throw new \InvalidArgumentException('Invalid resource definition');
            }
        }
        
        $this->resources = $resources;
    }
}