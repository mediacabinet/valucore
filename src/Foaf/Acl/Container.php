<?php
namespace Foaf\Acl;

use Foaf\Acl\Container\FactoryManager;
use Foaf\Acl\Container\Exception;

class Container
{

    /**
     * ACL registry
     * 
     * Array of ACL instances
     * 
     * @var array
     */
    private $registry;
    
    /**
     * Factory manager instance
     * 
     * @var \Foaf\Acl\Container\FactoryManager
     */
    private $factoryManager;

    /**
     * Register new ACL context
     * 
     * @param string $context
     * @param mixed $factory
     * @throws Exception\ContextAlreadyRegisteredException
     */
    public function registerContext($context, $factory = null){

        $context = $this->canonicalizeContext($context);

        if($this->has($context)){
            throw new Exception\ContextAlreadyRegisteredException('Context '.$context.' is already registered');
        }

        if($factory){
            $this->getFactoryManager()->setService($context, $factory);
        }

        $this->registry[$context] = null;
    }

    /**
     * Retrieve ACL instance by context
     * 
     * @param string $context
     * @throws Exception\ContextNotRegisteredException
     * @return \Zend\Permissions\Acl\Acl
     */
    public function get($context)
    {
        $context = $this->canonicalizeContext($context);

        if(!$this->has($context)){
            throw new Exception\ContextNotRegisteredException('Context '.$context.' has not been registered');
        }

        if($this->registry[$context] === null){
            $acl = $this->getCache() 
                ? $this->getCache()->getItem($context) : null;

            if(!$acl){
                $acl = $this->getFactory($context)->createAcl();

                if($this->getCache()){
                    $this->getCache()->setItem($context, $acl);
                }
            }

            $this->registry[$context] = $acl;
        }

        return $this->registry[$context];
    }

    /**
     * Does ACL context exist?
     * 
     * @param string $context
     * @return boolean
     */
    public function has($context)
    {
        return array_key_exists(
            $this->registry[$this->canonicalizeContext($context)]
        );
    }
    
    /**
     * Flush ACL instance cache for context
     * 
     * @param string $context
     */
    public function flush($context)
    {
        $context = $this->canonicalizeContext($context);

        if($this->getCache()){
            $this->getCache()->removeItem($context);
        }
    }    
    
    /**
     * Retrieve factory manager instance
     * 
     * @return \Foaf\Acl\Container\FactoryManager
     */
    public function getFactoryManager()
    {
        if(!$this->factoryManager){
            $this->factoryManager = new FactoryManager();
        }
        
        return $this->factoryManager;
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
        return $this->cache;
    }

    private function canonicalizeContext($context)
    {
        return strtolower($context);
    }
}