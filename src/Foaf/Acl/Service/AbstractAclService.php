<?php
namespace Foaf\Acl\Service;

use Foaf\Service\AbstractService;
use Foaf\Acl\Acl;

abstract class AbstractAclService extends AbstractService
{
    protected $optionsClass = 'Foaf\Acl\Service\AclServiceOptions';
    
    protected $cacheId;
    
    private $cache;
    
    private $acl;
    
    /**
     * Create a new ACL instance
     * 
     * @return \Zend\Permissions\Acl\Acl
     */
    public function createAcl()
    {
        return self::configureAcl();
    }
    
    public function getAcl()
    {
        if(!$this->acl){
            $acl = null;
            
            if($this->getCache()){
                $acl = $this->getCache()->getItem($this->getCacheId());
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
            
            $this->setAcl($acl);
        }
        
        return $this->acl;
    }
    
    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;
        
        if($this->getCache()){
            $this->getCache()->setItem($this->getCacheId(), $acl);
        }
    }
    
    public function flush()
    {
        $this->acl = null;
        $this->getCache()->removeItem($this->getCacheId());
    }
    
	public function isAllowed($role = null, $resource = null, $privilege = null)
	{
	 	return $this->getAcl()->isAllowed($role, $resource, $privilege);
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
	        $this->cache = $this->getServiceLocator()->get('FoafCache');
	    }
	    
	    return $this->cache;
	}
	
	protected function getCacheId()
	{
	    if(!$this->cacheId){
	        $this->cacheId = str_replace(array('\\', '/', '_'), '-', get_class($this));
	    }
	    
	    return $this->cacheId;
	}
	
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