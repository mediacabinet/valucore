<?php
namespace Foaf\Acl;

use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;
use Foaf\Acl\Role\Registry;

interface Backend{
	
	/**
	 * Causes the backend to reload its resources and
	 * rules from the backend storage
	 */
	public function flush();
	
	/**
	 * Get resource data
	 * 
	 * @return array	Resource data as an array where
	 * 					each item is in turn an indexed
	 * 					array with resource and its parent
	 */
	public function getResources();
	
	/**
	 * Get allow rules
	 * 
	 * @return array	Rule data as an array where
	 * 					each item is in turn an indexed
	 * 					array with roles, resources, privileges
	 * 					and assertion
	 */
	public function getAllow();
	
	/**
	 * Get deny rules
	 * 
	 * @return array	Rule data as an array where
	 * 					each item is in turn an indexed
	 * 					array with roles, resources, privileges
	 * 					and assertion
	 */
	public function getDeny();
		
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
	 * @see Zend\Permissions\Acl\Acl::addResource()
	 */
	public function createResource(ResourceInterface $resource, ResourceInterface $parent = null);
	
	/**
	 * @see Zend\Permissions\Acl\Acl::removeResource()
	 */
	public function removeResource(ResourceInterface $resource);
	
	/**
	 * @see Zend\Permissions\Acl\Acl::removeResourceAll()
	 */
	public function removeResourceAll();
	
	/**
	 * @see Zend\Permissions\Acl\Acl::allow()
	 */
	public function allow(array $roles = array(), array $resources = array(), array $privileges = array(), AssertionInterface $assert = null);
	
	/**
	 * @see Zend\Permissions\Acl\Acl::deny()
	 */
	public function deny(array $roles = array(), array $resources = array(), array $privileges = array(), AssertionInterface $assert = null);
	
	/**
	 * @see Zend\Permissions\Acl\Acl::removeAllow()
	 */
	public function removeAllow(array $roles = array(), array $resources = array(), array $privileges = array());
	
	/**
	 * @see Zend\Permissions\Acl\Acl::removeDeny()
	 */
	public function removeDeny(array $roles = array(), array $resources = array(), array $privileges = array());
	
	/**
	 * Remove all allow rules
	 * 
	 *  @return Backend
	 */
	public function removeAllowAll();
	
	/**
	 * Remove all deny rules
	 * 
	 * @return Backend
	 */
	public function removeDenyAll();
}