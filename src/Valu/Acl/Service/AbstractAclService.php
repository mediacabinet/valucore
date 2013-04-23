<?php
namespace Valu\Acl\Service;

use Zend\ServiceManager\Exception\ServiceNotFoundException;

use Valu\Acl\Role\IdentityRole;
use Valu\Service\Plugin\Auth\AclRole;
use ArrayAccess;
use Valu\Acl\Acl;
use ValuSo\Annotation as ValuService;
use ValuSo\Feature;
use ValuSo\Broker\ServiceBroker;
use Zend\Cache\Storage\StorageInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class AbstractAclService
    implements Feature\ConfigurableInterface,
               Feature\ServiceBrokerAwareInterface,
               Feature\IdentityAwareInterface,
               ServiceLocatorAwareInterface
{
    use Feature\OptionsTrait;
    use Feature\IdentityTrait;
    
    const CACHE_PREFIX = 'valu_acl_';
    
    protected $optionsClass = 'Valu\Acl\Service\AclServiceOptions';
    
    /**
     * Cache ID
     * 
     * @var string
     */
    protected $cacheId;
    
    /**
     * Service broker instance
     * 
     * @var \ValuSo\Broker\ServiceBroker
     */
    protected $serviceBroker;
    
    /**
     * Service broker instance
     *
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;
    
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
     * Flush (reload) ACL
     * 
     * @ValuService\Context("*")
     */
    public function flush()
    {
        $this->acl = null;
        
        if ($this->getCache()) {
            $this->getCache()->removeItem($this->getCacheId());
        }
        
        return true;
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
     * @return boolean
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
     * Create a new ACL instance
     * 
     * @return \Zend\Permissions\Acl\Acl
     * 
     * @ValuService\Exclude
     */
    public function createAcl()
    {
        return self::configureAcl();
    }
    
    /**
     * Retrieve Valu\Acl\Ac instance
     * 
     * @return \Valu\Acl\Acl
     * 
     * @ValuService\Exclude
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
                $acl = $this->createAcl();
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
     * 
     * @ValuService\Exclude
     */
    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;
    }
	
	/**
	 * Set cache
	 *
	 * @param \Zend\Cache\Storage\Adapter $cache
	 * @return Acl
	 * 
	 * @ValuService\Exclude
	 */
	public function setCache(StorageInterface $cache){
	    $this->cache = $cache;
	    return $this;
	}
	
	/**
	 * Retrieve cache
	 *
	 * @return \Zend\Cache\Storage\Adapter
	 * 
	 * @ValuService\Exclude
	 */
	public function getCache(){
	    if(!$this->cache){
	        try{
	            $cache = $this->getServiceLocator()->get('Cache');
	        } catch (ServiceNotFoundException $e) {
	            return null;
	        }
	        
	        if ($cache instanceof StorageInterface) {
	            $this->setCache($cache);
	        }
	    }
	    
	    return $this->cache;
	}
	
	/**
     * Retrieve service broker instance
     * 
     * @return \ValuSo\Broker\ServiceBroker
     * 
     * @ValuService\Exclude
	 */
	public function getServiceBroker()
	{
	    return $this->serviceBroker;
	}
	
	/**
     * @see \ValuSo\Feature\ServiceBrokerAwareInterface::setServiceBroker()
     * 
     * @ValuService\Exclude
     */
    public function setServiceBroker(ServiceBroker $serviceBroker)
    {
        $this->serviceBroker = $serviceBroker;
    }
    
    /**
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::getServiceLocator()
     * 
     * @ValuService\Exclude
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    /**
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::setServiceLocator()
     * 
     * @ValuService\Exclude
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

	/**
	 * Retrieve cache ID
	 * 
	 * @return string
	 */
	protected function getCacheId()
	{
	    if(!$this->cacheId){
	        $this->cacheId = md5(str_replace(array('\\', '/', '_'), '-', self::CACHE_PREFIX . get_class($this)));
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
     * Create and retrieve identity aware role for current identity
     * 
     * @param string $ns
     * @return \Valu\Acl\Role\UniversalRole
     */
    protected function createIdentityRole($ns = self::ROOT)
    {
        if (!$this->identity) {
            throw new \RuntimeException(
                'Unable to create identity role, as identity is not set');
        }
        
        $role = $this->getAclRole($ns);
        
        $aclRole = new IdentityRole($role, $this->identity);
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
    protected function getAclRole($ns = self::ROOT)
    {
        $roles = isset($this->identity['roles']) 
            ? $this->identity['roles'] : [];
        
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