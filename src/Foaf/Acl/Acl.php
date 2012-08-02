<?php
namespace Foaf\Acl;

use Foaf\Acl\Backend,
	Foaf\Acl\Role\Registry,
	Zend\Permissions\Acl\Exception,
	Zend\Permissions\Acl\Assertion;

class Acl extends \Zend\Permissions\Acl\Acl{
	
	/**
	 * Backend implementation
	 * 
	 * @var Backend
	 */
	protected $backend;
	
	/**
	 * Is auto-flush enabled?
	 * 
	 * @var boolean
	 */
	protected $useAutoFlush = false;
	
	/**
	 * Properties that need to be reseted when
	 * flushed
	 * 
	 * @var array
	 */
	protected $resets = array(
		'resources' 			=> null,
		'isAllowedRole' 		=> null,
		'isAllowedResource' 	=> null,
		'isAllowedPrivilege' 	=> null,
		'rules' 				=> null
	);
	
	public function __construct(Backend $backend, Registry $roleRegistry){
		
		foreach ($this->resets as $property => &$value){
			$value = $this->{$property};
		}
		
		$this->_roleRegistry 	= $roleRegistry;
		$this->backend 			= $backend;
		
		$this->_flush(true);
	}
	
	/**
	 * Flush all ACL data
	 *
	 * Resets ACL data and reloads it from the backend.
	 *
	 * @param boolean $new
	 * @return Acl
	 */
	public function flush(){
		$this->_flush(false);
	}
	
	/**
	 * Enable auto-flush after each operation
	 * that changes ACL state
	 *  
	 * @param boolean $use
	 * @return Acl
	 */
	public function setUseAutoFlush($use)
	{
		$this->useAutoFlush = (bool) $use;
		$this->_roleRegistry->setUseAutoFlush($this->useAutoFlush);
		
		return $this;
	}
	
	/**
	 * Is auto-flush enabled?
	 * 
	 * @return boolean
	 */
	public function getUseAutoFlush()
	{
		return $this->useAutoFlush;
	}

	/**
	 * (non-PHPdoc)
	 * @see Zend\Permissions\Acl.Acl::addResource()
	 */
	public function addResource($resource, $parent = null)
	{
		parent::addResource($resource, $parent);
		
		$this->backend->createResource(
			$this->getResource($resource),
			($parent === null) ? null : $this->getResource($parent)
		);
		
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Zend\Permissions\Acl.Acl::removeResource()
	 */
	public function removeResource($resource)
	{
		/**
		 * Try fetching the resource but let parent
		 * handle exceptions
		 */
		try{
			$removed = $this->getResource($resource);
		}catch (Exception $e){}
		
		parent::removeResource($resource);
		
		$this->backend->removeResource(
			$removed
		);
			
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Zend\Permissions\Acl.Acl::removeResourceAll()
	 */
	public function removeResourceAll()
	{
		parent::removeResourceAll();
		
		$this->backend->removeResourceAll();
		
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Zend\Permissions\Acl.Acl::allow()
	 */
	public function allow($roles = null, $resources = null, $privileges = null, Assertion $assert = null)
	{
		parent::allow($roles, $resources, $privileges, $assert);
		
		$this->backend->allow(
			$this->normalizeRoleArray($roles),
			$this->normalizeResourceArray($resources),
			$this->normalizePrivilegeArray($privileges),
			$assert
		);
			
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Zend\Permissions\Acl.Acl::deny()
	 */
	public function deny($roles = null, $resources = null, $privileges = null, Assertion $assert = null)
	{
		parent::deny($roles, $resources, $privileges, $assert);
		
		$this->backend->deny(
			$this->normalizeRoleArray($roles),
			$this->normalizeResourceArray($resources),
			$this->normalizePrivilegeArray($privileges),
			$assert
		);
			
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Zend\Permissions\Acl.Acl::removeAllow()
	 */
	public function removeAllow($roles = null, $resources = null, $privileges = null)
	{
		parent::removeAllow($roles, $resources, $privileges);
		
		$this->backend->removeAllow(
			$this->normalizeRoleArray($roles),
			$this->normalizeResourceArray($resources),
			$this->normalizePrivilegeArray($privileges)
		);
			
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Zend\Permissions\Acl.Acl::removeDeny()
	 */
	public function removeDeny($roles = null, $resources = null, $privileges = null)
	{
		parent::removeDeny($roles, $resources, $privileges);
		
		$this->backend->removeDeny(
			$this->normalizeRoleArray($roles),
			$this->normalizeResourceArray($resources),
			$this->normalizePrivilegeArray($privileges)
		);
			
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $this;
	}
	
	/**
	 * Removes all ACL resources, roles and rules
	 * 
	 * @return Acl
	 */
	public function clear()
	{
		foreach ($this->resets as $property => $value){
			$this->{$property} = $value;
		}
		
		$this->backend->removeResourceAll();
		$this->backend->removeAllowAll();
		$this->backend->removeDenyAll();
		
		$this->_roleRegistry->removeAll();
		
		return $this;
	}
	
	/**
	 * @see flush();
	 */
	protected function _flush($new = false)
	{
		if(!$new){
			foreach ($this->resets as $property => $value){
				$this->{$property} = $value;
			}
	
			$this->_roleRegistry->flush();
		}
	
		/**
		 * Flush back end
		 */
		$this->backend->flush();
	
		/**
		 * Get and set resources
		 * @var array
		 */
		$resources = $this->backend->getResources();
	
		if(sizeof($resources)){
			foreach ($resources as $data){
				list($resource, $parent) = $data;
	
				/**
				 * Skip if reference to a non-existent parent
				 * resource
				 */
				if(!$this->hasResource($parent)){
					//continue;
				}
	
				parent::addResource($resource, $parent);
			}
		}
	
		/**
		 * Get and set allow rules
		 * @var array
		 */
		$allow = $this->backend->getAllow();
	
		if(sizeof($allow)){
			foreach ($allow as $data){
				list($roles, $resources, $privileges, $assert) = $data;
	
				/**
				 * Filter roles and resources by removing such, that don't
				 * exist in current ACL instance. Ensure that rule is not
				 * extended to a global rule by skipping such iterations
				 * that exclude all rules/resources in filter.
				 */
				$rolesTmp = $this->filterRoles($roles);
	
				if(sizeof($roles) && !sizeof($rolesTmp)) continue;
				else $roles = $rolesTmp;
	
				$resourcesTmp = $this->filterResources($resources);
	
				if(sizeof($resources) && !sizeof($resourcesTmp)) continue;
				else $resources = $resourcesTmp;
				
				parent::allow($roles, $resources, $privileges, $assert);
			}
		}
	
		/**
		 * Get and set deny rules
		 * @var array
		 */
		$deny = $this->backend->getDeny();
	
		if(sizeof($deny)){
			foreach ($deny as $data){
				list($roles, $resources, $privileges, $assert) = $data;
	
				/**
				 * Filter roles and resources by removing such, that don't
				 * exist in current ACL instance. Ensure that rule is not
				 * extended to a global rule by skipping such iterations
				 * that exclude all rules/resources in filter.
				 */
				$rolesTmp = $this->filterRoles($roles);
	
				if(sizeof($roles) && !sizeof($rolesTmp)) continue;
				else $roles = $rolesTmp;
	
				$resourcesTmp = $this->filterResources($resources);
	
				if(sizeof($resources) && !sizeof($resourcesTmp)) continue;
				else $resources = $resourcesTmp;
				
				parent::deny($roles, $resources, $privileges, $assert);
			}
		}
	}
	
	/**
	 * Normalize value to array
	 * 
	 * @param mixed $roles
	 * @return array
	 */
	protected function normalizeRoleArray($roles){
	 	if($roles === null){
			return array();
		} else if (!is_array($roles)) {
            $roles = array($roles);
        } else if (0 === count($roles)) {
            $roles = array(null);
        }
        
        $rolesTemp = $roles;
        $roles = array();
        foreach ($rolesTemp as $role) {
            if (null !== $role) {
                $roles[] = $this->_getRoleRegistry()->get($role);
            }
        }
        unset($rolesTemp);
        
        return $roles;
	}
	
	/**
	 * Normalize value to array
	 * 
	 * @param mixed $resources
	 * @return array
	 */
	protected function normalizeResourceArray($resources){
		if($resources === null){
			return array();
		} else if (!is_array($resources)) {
            $resources = ($resources == null && count($this->_resources) > 0) ? array_keys($this->_resources) : array($resources);
        } else if (0 === count($resources)) {
            $resources = array(null);
        }
        
        $resourcesTemp = $resources;
        $resources = array();
        foreach ($resourcesTemp as $resource) {
            if (null !== $resource) {
                $resources[] = $this->getResource($resource);
            }
        }
        unset($resourcesTemp);
        
        return $resources;
	}
	
	/**
	 * Normalize value to array
	 * 
	 * @param mixed $privileges
	 * @return array
	 */
	protected function normalizePrivilegeArray($privileges)
	{
		if (null === $privileges) {
            return array();
        } else if (!is_array($privileges)) {
            $privileges = array($privileges);
        }
        
        return $privileges;
	}
	
	/**
	 * Filter array of roles by removing such
	 * roles that don't exist
	 * 
	 * @param array $roles
	 * @return array Filtered array
	 */
	private function filterRoles(array $roles){
		if(sizeof($roles)){
			foreach ($roles as $key => $role){
				if(!$this->hasRole($role)){
					unset($roles[$key]);
				}
			}
		}
		
		return $roles;
	}
	
	/**
	 * Filter array of resources by removing such
	 * resources that don't exist
	 * 
	 * @param array $resources
	 * @return array Filtered array
	 */
	private function filterResources(array $resources){
		if(sizeof($resources)){
			foreach ($resources as $key => $resource){
				if(!$this->hasResource($resource)){
					unset($resources[$key]);
				}
			}
		}
		
		return $resources;
	}
}