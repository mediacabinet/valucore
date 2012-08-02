<?php
namespace Foaf\Acl\Context;

use Foaf\Acl\Role\Registry as RoleRegistry;

interface Repository{

	/**
	 * Create a new ACL context
	 * 
	 * @param string $name
	 * @return Foaf\Acl\Acl
	 */
	public function createAclContext($name);
	
	/**
	 * Does a context exist?
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasContext($name);
	
	/**
	 * Retrieve context by name
	 * 
	 * @param string $name
	 * @return Foaf\Acl\Acl
	 * @throws Exception
	 */
	public function getContext($name);
	
	/**
	 * Get context names as an array
	 * 
	 * @return array
	 */
	public function getContextNames();
	
	/**
	 * Retrieve role registry by context name
	 * 
	 * Creates a new registry instance if not previously
	 * initialized.
	 * 
	 * @param string $name
	 * @return RoleRegistry
	 */
	public function getRoleRegistry($name);
	
	/**
	 * Set role registry identified by context name
	 * 
	 * @param string $name
	 * @param RoleRegistry $registry
	 * @return Repository Provides fluent interface
	 */
	public function setRoleRegistry($name, RoleRegistry $registry);
}