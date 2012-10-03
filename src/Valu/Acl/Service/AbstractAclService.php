<?php
namespace Valu\Acl\Service;

use Valu\Service\AbstractService;
use Valu\Acl\Acl;
use Zend\Cache\Storage\StorageInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class AbstractAclService extends AbstractService
{
    protected $optionsClass = 'Valu\Acl\Service\AclServiceOptions';
    
    /**
     * Cache ID
     * 
     * @var string
     */
    protected $cacheId;
    
    /**
     * Cache adapter
     * 
     * @var \Zend\Cache\Storage\StorageInterface
     */
    private $cache;
    
    /**
     * Acl instance
     * 
     * @var \Valu\Acl\Acl
     */
    private $acl;
    
    /**
     * Allow all state
     * 
     * @var boolean
     */
    private $allowAll = false;
    
    /**
     * Deny all state
     * 
     * @var boolean
     */
    private $denyAll = false;
    
    /**
     * Create a new ACL instance
     * 
     * @return \Zend\Permissions\Acl\Acl
     */
    public function createAcl()
    {
        return self::configureAcl();
    }
    
    /**
     * Retrieve Valu\Acl\Ac instance
     * 
     * @return \Valu\Acl\Ac
     */
    public function getAcl()
    {
        if(!$this->acl){
            $acl = null;
            $cached = false;
            
            if($this->getCache()){
                $acl = $this->getCache()->getItem($this->getCacheId());

                if($acl){
                    $cached = true;
                }
            }
            
            if(!$acl){
                // If invoked via service broker, make sure event listeners
                // have a chance
                if($this->getEvent() && $this->getServiceBroker()){
                    $acl = $this->getServiceBroker()
                        ->service($this->getEvent()->getService())
                        ->createAcl();
                }
                else{
                    $acl = $this->createAcl();
                }
            }
            
            if(!$cached && $this->getCache()){
                $this->getCache()->setItem($this->getCacheId(), $acl);
            }
            
            $this->setAcl($acl);
        }
        
        return $this->acl;
    }
    
    /**
     * Set Acl instance
     * 
     * @param \Valu\Acl\Acl $acl
     */
    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;
    }
    
    /**
     * Flush (reload) ACL
     * 
     */
    public function flush()
    {
        $this->acl = null;
        $this->getCache()->removeItem($this->getCacheId());
    }
    
    /**
     * Retrieve allow all state
     * 
     * @return boolean
     */
    public function getAllowAll()
    {
        return $this->allowAll;
    }
    
    /**
     * Allow everything for everyone in current session
     * or until disabled
     * 
     * @valu\service\context native
     */
    public function setAllowAll($allow = true)
    {
        $oldState = $this->allowAll;
        $this->allowAll = $allow;
        return $oldState;
    }
    
    /**
     * Retrieve deny all state
     * 
     * @return boolean
     */
    public function getDenyAll()
    {
        return $this->denyAll;
    }
    
    /**
     * Deny everything for everyone
     * 
     * @valu\service\context native
     */
    public function setDenyAll($deny = true)
    {
        $oldState = $this->denyAll;
        $this->denyAll = $deny;
        return $oldState;
    }
    
    /**
     * Test whether given role has privilege to resource(s) 
     * 
     * @param string $role
     * @param string|array|null $resource
     * @param string|null $privilege
     */
	public function isAllowed($role = null, $resource = null, $privilege = null)
	{
	    if ($this->getDenyAll()) {
	        return false;
	    }
	    
	    if ($this->getAllowAll()) {
	        return true;
	    }
	    
	 	return $this->getAcl()->isAllowed(
 	        $role, 
 	        $resource, 
 	        $privilege);
	}
	
	/**
	 * Set cache
	 *
	 * @param \Zend\Cache\Storage\Adapter $cache
	 * @return Acl
	 */
	public function setCache(StorageInterface $cache){
	    $this->cache = $cache;
	    return $this;
	}
	
	/**
	 * Retrieve cache
	 *
	 * @return \Zend\Cache\Storage\Adapter
	 */
	public function getCache(){
	    if(!$this->cache){
	        $this->setCache($this->getServiceLocator()->get('ValuCache'));
	    }
	    
	    return $this->cache;
	}
	
	/**
	 * Retrieve cache ID
	 * 
	 * @return string
	 */
	protected function getCacheId()
	{
	    if(!$this->cacheId){
	        $this->cacheId = md5(str_replace(array('\\', '/', '_'), '-', get_class($this)));
	    }
	    
	    return $this->cacheId;
	}
	
	/**
	 * Configure Acl
	 * 
	 * @param array|null $config
	 * @return \Valu\Acl\Acl
	 */
	protected function configureAcl($config = null)
	{
	    
	    if(is_null($config)){
	        $config = $this->getOptions()->toArray();
	    }

	    // Create new Acl instance and inject service locator
	    $acl = new Acl();
	    $acl->setServiceLocator($this->getServiceLocator());
	    
	    /**
	     * Register ACL roles
	     */
	    foreach ($config['roles'] as $role) {
	        if (!$acl->hasRole($role['id'])) {
	            $acl->addRole($role['id'], $role['parents']);
	        }
	    }
	    
	    /**
	     * Register ACL resources
	     */
	    foreach ($config['resources'] as $resource) {
	        if (!$acl->hasResource($resource['id'])) {
	    
	            if (!isset($resource['parent'])){
	                $resource['parent'] = null;
	            }
	    
	            $acl->addResource($resource['id'], $resource['parent']);
	        }
	    }
	    
	    /**
	     * Add allow and deny rules
	     */
	    $this->addRules($acl, Acl::TYPE_ALLOW, $config['allow']);
	    $this->addRules($acl, Acl::TYPE_DENY, $config['deny']);
	    
	    return $acl;
	}
	
	/**
	 * Add rules
	 * 
	 * @param Acl $acl
	 * @param unknown_type $type
	 * @param unknown_type $rules
	 */
	private function addRules(Acl $acl, $type, $rules){
	    foreach ($rules as $key => $specs) {
	         
	        if(!isset($specs['roles'])){
	            $specs['roles'] = $key;
	        }
	         
	        if(!isset($specs['resources'])){
	            $specs['resources'] = null;
	        }
	         
	        if(!isset($specs['privileges'])){
	            $specs['privileges'] = null;
	        }
	        
	        if(!isset($specs['assertion'])){
	            $specs['assertion'] = null;
	        }
	        
	        if(is_string($specs['assertion'])){
	            $specs['assertion'] = new $specs['assertion'];
	        }
	         
	        $acl->setRule(
                Acl::OP_ADD, 
                $type, 
                $specs['roles'], 
                $specs['resources'], 
                $specs['privileges'], 
                $specs['assertion']
            );
	    }
	}
}