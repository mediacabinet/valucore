<?php
namespace Foaf\Acl\Role;

use Zend\Permissions\Acl\Role\RoleInterface,
	Foaf\Acl\Role\Registry\Backend;

class Registry extends \Zend\Permissions\Acl\Role\Registry
{
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
	
	public function __construct(Backend $backend){
		$this->backend = $backend;
		$this->flush();
	}
	
	public function flush()
	{
		$this->_roles = array();
		
		$roles = $this->backend->getRoles();
	
		if(sizeof($roles)){
			foreach($roles as $data){
				list($role, $parents) = $data;
				
				parent::add($role, $parents);
			}
		}
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
	
	public function add(RoleInterface $role, $parents = null)
	{
		$ret = parent::add($role, $parents);
		
		$this->backend->createRole(
			$this->get($role),
			$this->getParents($role)
		);
		
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $ret;
	}
	
	public function remove($role)
	{
		$role	= $this->get($role);
		$ret	= parent::remove($role);
		
		$this->backend->removeRole(
			$role
		);
		
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $ret;
	}
	
	public function removeAll()
	{
		$ret = parent::removeAll();
		
		$this->backend->removeRoleAll();
		
		if($this->getUseAutoFlush()){
			$this->flush();
		}
		
		return $ret;
	}
}