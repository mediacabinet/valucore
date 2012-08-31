<?php
namespace Foaf\InputFilter;

use Zend\InputFilter\InputInterface;

use \ArrayObject;
use Zend\Stdlib\ArrayUtils;
use Foaf\InputFilter\Repository\DelegateManager;
use Zend\Stdlib\PriorityQueue;
use Zend\Stdlib\ErrorHandler;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\Factory;
use Zend\Cache\Storage\StorageInterface;

class InputFilterRepository
{
    /**
     * Default priority at which delegates are added
     */
    const DEFAULT_PRIORITY = 1000;
    
    /**
     * Cache namespace
     * 
     * @var string
     */
    const CACHE_NS = 'foaf_input_filter_';
    
    /**
     * Input filter instances
     * 
     * @var array
     */
    private $inputFilters = array();
    
    /**
     * Array of input filter object hashes
     * 
     * @var array
     */
    private $inputFilterHashes = array();
    
    /**
     * References sub input filter names
     * 
     * @var array
     */
    private $references = null;
    
    /**
     * Mark references changed
     * 
     * @var boolean
     */
    private $dirtyReferences = false;
    
    /**
     * Delegate manager
     * 
     * @var \Foaf\InputFilter\Repository\DelegateManager
     */
    private $plugins;
    
    /**
     * Shared delegates
     *  
     * @var \Zend\Stdlib\PriorityQueue
     */
    private $delegates;
    
    /**
     * Default input filter class
     * 
     * @var string
     */
    private $defaultClass = 'Zend\InputFilter\InputFilter';
    
    /**
     * Cache
     * 
     * @var \Zend\Cache\Storage\StorageInterface
     */
    private $cache;
    
    public function __construct()
    {
        $this->delegates = new PriorityQueue();
        $this->plugins = new DelegateManager();
    }
    
    /**
     * Get input filter
     * 
     * @return \Zend\InputFilter\InputFilterInterface
     */
    public function get($name)
    {
        if(!$name){
            return null;
        }
        
        if(!isset($this->inputFilters[$name])){
            
            $inputFilter = null;
            $cached = false;
            $cacheId = $this->getCacheId($name);
        
            if($this->getCache()){
                
                $inputFilter = $this->getCache()->getItem(
                    $cacheId
                );
                
                $references = $this->getCache()->getItem(
                    $cacheId.'-references'
                );
                
                $this->references[$name] = is_array($references) 
                    ? $references : array();
                
                if($inputFilter){
                    $cached = true;
                }
            }
        
            if(!$inputFilter){
                $inputFilter = $this->create($name);
            }
            
            if(!$cached && $this->getCache()){
                
                $this->getCache()->setItem(
                    $cacheId, 
                    $inputFilter);
                
                // Remember references
                $this->getCache()->setItem(
                    $cacheId.'-references',
                    $this->getReferences($name));
            }
        
            $this->set($name, $inputFilter);
        }
        
        return $this->inputFilters[$name];
    }
    
    /**
     * Set input filter
     * 
     * @param InputFilterInterface $inputFilter
     */
    public function set($name, InputFilterInterface $inputFilter)
    {
        $this->inputFilters[$name] = $inputFilter;
        $this->inputFilterHashes[spl_object_hash($inputFilter)] = $name; 
    }
    
    /**
     * Retrieve input filter specifications
     * 
     * @param string $name
     * @return array
     */
    public function getSpecifications($name)
    {
        $delegates = $this->getDelegates($name);
        return $this->internalGetSpecifications($name, $delegates);
    }
    
    /**
     * Flush data, clear current input filter and cache record for
     * given namespace
     * 
     */
    public function reload($name)
    {
       $this->cascadeReload($name, array());
    }
    
    /**
     * Add new delegate
     * 
     * @param string|\Foaf\InputFilter\Repository\Delegate\DelegateInterface $delegate
     * @param string $name Input filter name
     * @param array $creationOptions
     * @param int $priority Delegate processing priority (greatest priority is processed first)
     * @throws \InvalidArgumentException
     */
    public function addDelegate($delegate, $name = null, $creationOptions = array(), $priority = self::DEFAULT_PRIORITY)
    {
        $data = new \ArrayObject(array(
            'delegate' => $delegate,
            'name'     => $name,
            'options'  => $creationOptions
        ));
        
        if($this->delegates->contains($data)){
            throw new \InvalidArgumentException('Another delegate is already registered with same parameters');
        }

        $this->delegates->insert($data, $priority);
        
        if(is_string($delegate) && class_exists($delegate)){
            $this->plugins->setInvokableClass($delegate, $delegate);
        }
    }
    
    /**
     * Access to delegate plugin manager
     * 
     * Note that new delegates cannot be registered directly via
     * plugin manager.
     * 
     * @return \Foaf\InputFilter\Repository\DelegateManager
     */
    public function getPlugins()
    {
        return $this->plugins;
    }
    
    /**
     * Set default input filter class
     * 
     * @param string $class
     * @throws \InvalidArgumentException
     * @return \Foaf\InputFilter\Repository
     */
    public function setDefaultInputFilterClass($class)
    {
        if(!class_exists($class)){
            throw new \InvalidArgumentException('Expected valid class name');
        }
        
        $this->defaultClass = $class;
        return $this;
    }
    
    /**
     * Retrieve default input filter class
     * 
     * @return string
     */
    public function getDefaultInputFilterClass()
    {
        return $this->defaultClass;
    }
    
    /**
     * Retrieve input filter factory
     * 
     * @return Factory
     */
    public function getFactory()
    {
        if(!$this->factory){
            $this->setFactory(new Factory());
        }
        
        return $this->factory;
    }
    
    /**
     * Set input filter factory
     * 
     * @param Factory $factory
     */
    public function setFactory(Factory $factory)
    {
        $this->factory = $factory;
    }
    
    /**
     * Create new input filter
     * 
     * @return \Zend\InputFilter\InputFilterInterface
     */
    protected function create($name)
    {
        $references = array();
        $delegates  = $this->getDelegates($name);
        $specs      = $this->internalGetSpecifications($name, $delegates);
        
        // Set type, if not defined
        if(!isset($specs['type'])){
            $specs['type'] = $this->getDefaultInputFilterClass();
        }
        // Add parent reference to this
        else if(!class_exists($specs['type'])){
            $references[$specs['type']][] = $name;
        }
        
        // Attach inputs and input filters later...
        $extra = array();
        foreach($specs as $name => $input){
            if( $input instanceof InputInterface || 
                $input instanceof InputFilterInterface){
                
                // Test whether or not child input filter is
                // manager by this Manager, and if it is
                // add refecenses accordingly
                if($input instanceof InputFilterInterface){
                    $child = array_search(spl_object_hash($input), $this->inputFilterHashes);
                    
                    if($child !== false){
                        $references[$child][] = $name;
                    }
                }
                
                $extra[$name] = $input;
                unset($specs[$name]);
            }
        }
        
        // Create 'main' input filter
        $factory = $this->getFactory();
        $inputFilter = $factory->createInputFilter($specs);
        
        // Attach inputs and input filters
        foreach($extra as $name => $input){
            $inputFilter->add($input, $name);
        }
        
        // Let delegates finalize the input filter
        foreach($delegates as $delegate){
            $delegate->finalizeInputFilter(
                $this,
                $name,
                $inputFilter
            );
        }
        
        // Clear and set new references
        $this->clearReferencesTo($name);
        $this->addReferences($references);
        
        return $inputFilter;
    }
    
    /**
     * Retrieve input filter specifications
     * 
     * @param string $name
     * @param arrayÊ $delegates
     * @return array
     */
    protected function internalGetSpecifications($name, arrayÊ$delegates)
    {
        $specs = array();
        
        // Merge specifications provided by delegates
        foreach($delegates as $delegate){
            $specs = ArrayUtils::merge(
                $specs,
                $delegate->getInputFilterSpecifications(
                    $this,
                    $name
                )
            );
        }
        
        $specs = new ArrayObject($specs);
        
        // Let delegates prepare the specifications
        foreach($delegates as $delegate){
            $delegate->prepareInputFilterSpecifications($this, $name, $specs);
        }
        
        return $specs->getArrayCopy();
    }
    
    /**
     * Get delegates associated with given filter anme
     * 
     * @param string $name
     * @return array
     */
    protected function getDelegates($name)
    {
        $delegates = array();
        $queue     = clone $this->delegates;
        
        foreach($queue as $data){
            
            if($data['name'] == $name || $data['name'] === null){
                
                if(is_string($data['delegate'])){
                    $data['delegate'] = $this->plugins->get($data['delegate']);
                }
                
                $delegates[] = $data['delegate'];
            }
        }
        
        return $delegates;
    }
    
    /**
     * Cascade reload to references input filters
     * 
     * @param string $name
     * @param array $reloaded
     */
    protected function cascadeReload($name, array & $reloaded)
    {
        // Skip, if already reloaded
        if(in_array($name, $reloaded)){
            return;
        }
    
        if(isset($this->inputFilters[$name])){
            unset($this->inputFilters[$name]);
            unset($this->inputFilterHashes[$name]);
        }
    
        // Remove from cache
        if($this->getCache()){
            $this->getCache()->removeItem($this->getCacheId($name));
        }
    
        // Mark reloaded
        $reloaded[] = $name;
    
        // Fetch references and reload each
        foreach($this->getReferences($name) as $name){
            $this->cascadeReload($name, $reloaded);
        }
    }
    
    /**
     * Clear all references to given input filter name
     * 
     * @param string $name
     */
    protected function clearReferencesTo($name)
    {
        $this->getReferences();
        
        foreach ($this->references as $key => $refs){
            $refKey = array_search($name, $refs);
            
            if($refKey !== false){
                unset($this->references[$key][$refKey]);
                $this->dirtyReferences = true;
            }
        }
    }
    
    /**
     * Add references from array
     * 
     * @param array $references
     */
    protected function addReferences(array $references)
    {
        $this->getReferences();
        
        foreach($references as $name => $refs){
            if(!isset($this->references[$name])){
                $this->references[$name]   = $refs;
                $this->dirtyReferences     = true;
            }
            else{
                foreach($refs as $refName){
                    if(!in_array($refName, $this->references[$name])){
                        $this->references[$name][] = $refName;
                        $this->dirtyReferences = true;
                    }
                }
            }
        }
    }
    
    /**
     * Retrieve all references or named input filter
     * references
     * 
     * @param string $name
     * @return array
     */
    protected function getReferences($name = null)
    {
        if(!$this->references && $this->getCache()){
            $this->references = $this->getCache(self::CACHE_NS . '_related');
            
            if(!is_array($this->references)){
                $this->references = array();
            }
        }
        
        if($name){
            return isset($this->references[$name])
                ? $this->references[$name]
                : array();
        }
        else{
            return $this->references;
        }
    }
    
    /**
     * Cache current references (only when changed)
     */
    protected function cacheReferences()
    {
        if($this->getCache() && $this->references && $this->dirtyReferences){
            $this->getCache()->set(
                self::CACHE_NS . '_related',
                $this->references    
            );
        }
    }
    
    /**
	 * Set cache
	 *
	 * @param \Zend\Cache\Storage\StorageInterface $cache
	 * @return Acl
	 */
	public function setCache(StorageInterface $cache){
	    $this->cache = $cache;
	    return $this;
	}
	
	/**
	 * Retrieve cache
	 *
	 * @return \Zend\Cache\Storage\StorageInterface
	 */
	public function getCache(){
	    return $this->cache;
	}
	
	/**
	 * Retrieve cache ID
	 * 
	 * @return string
	 */
	protected function getCacheId($name)
	{
	    return md5(self::CACHE_NS . $name);
	}
}