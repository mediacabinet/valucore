<?php
namespace Foaf\Acl\Role\Registry;

use Zend\Permissions\Acl\Role\Registry;
use Zend\Permissions\Acl\Role\RoleInterface as Role;

interface Backend{
	
	/**
	 * Set backend context
	 * 
	 * Initializes backend instance with
	 * context name.
	 * 
	 * @param string $context
	 */
	public function setContext($context);

	/**
	 * Retrieve all stored roles
	 * 
	 * @return array 	Array of roles, where each value
	 * 					contains array with two items:
	 * 					the actual role and its parents
	 */
	public function getRoles();
	
	/**
	 * @see Zend\Permissions\Acl\Acl::addRole()
	 */
	public function createRole(Role $role, array $parents = null);
	
	/**
	 * @see Zend\Permissions\Acl\Acl::removeRole()
	 */
	public function removeRole(Role $role);
	
	/**
	 * @see Zend\Permissions\Acl\Acl::removeRoleAll()
	 */
	public function removeRoleAll();
	
}