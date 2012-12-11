<?php
namespace Valu\Service\Plugin\Auth;

use Valu\Service\Plugin\Auth;
use Valu\Acl\Role\UniversalRole;
use Zend\Permissions\Acl\Role\GenericRole;

class AclRole extends UniversalRole
{
    /**
     * Auth plugin
     * 
     * @var Auth
     */
    private $plugin;
    
	public function __construct($roleId, Auth $plugin)
    {
        $this->setPlugin($plugin);
        parent::__construct($roleId, $plugin->getId());
    }
    
    /**
     * @return \Valu\Service\Plugin\Auth
     */
    public function getPlugin()
    {
        return $this->plugin;
    }
    
    /**
     * @param \Valu\Service\Plugin\Auth $plugin
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }
    
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->getPlugin(), $method), $args);
    }
}