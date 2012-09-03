<?php
namespace Foaf\InputFilter;

use Zend\InputFilter\InputInterface;
use \ArrayObject;
use Zend\Stdlib\ArrayUtils;
use Foaf\InputFilter\Configurator\DelegateManager;
use Zend\Stdlib\PriorityQueue;
use Zend\Stdlib\ErrorHandler;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\Factory;
use Zend\Cache\Storage\StorageInterface;

class Configurator implements ConfiguratorInterface
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
    const CACHE_NS = 'foaf_input_filter_configurator_';

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
     * @var \Foaf\InputFilter\Configurator\DelegateManager
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
     * Input filter factory instance
     *
     * @var \Zend\InputFilter\Factory
     */
    private $factory;

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
     * Create new input filter
     *
     * @return \Zend\InputFilter\InputFilterInterface
     */
    public function configure($name)
    {
        $references = array();
        $delegates = $this->getDelegates($name);
        $specs = $this->internalGetSpecifications($name, $delegates, 
                $references);
        
        // Set type, if not defined
        if (! isset($specs['type'])) {
            $specs['type'] = $this->getDefaultInputFilterClass();
        }
        
        // Attach inputs and input filters later...
        $extra = array();
        foreach ($specs as $name => $input) {
            if ($input instanceof InputInterface ||
                $input instanceof InputFilterInterface) {
                
                $extra[$name] = $input;
                unset($specs[$name]);
            }
        }
        
        // Create 'main' input filter
        $factory = $this->getFactory();
        $inputFilter = $factory->createInputFilter($specs);
        
        // Attach inputs and input filters
        foreach ($extra as $name => $input) {
            $inputFilter->add($input, $name);
        }
        
        // Let delegates finalize the input filter
        foreach ($delegates as $delegate) {
            $delegate->finalizeInputFilter($this, $name, $inputFilter);
        }
        
        // Clear and set new references
        $this->clearReferencesTo($name);
        $this->addReferences($references);
        $this->flushReferenceCache();
        
        return $inputFilter;
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
        $references = array();
        return $this->internalGetSpecifications($name, $delegates, $references);
    }

    /**
     * Add new delegate
     *
     * @param string|\Foaf\InputFilter\Configurator\Delegate\DelegateInterface $delegate            
     * @param string $name
     *            Input filter name
     * @param array $creationOptions            
     * @param int $priority
     *            Delegate processing priority (greatest priority is processed
     *            first)
     * @throws \InvalidArgumentException
     */
    public function addDelegate($delegate, $name = null, $creationOptions = array(), 
            $priority = self::DEFAULT_PRIORITY)
    {
        $data = new \ArrayObject(
            array(
                'delegate' => $delegate,
                'name' => $name,
                'options' => $creationOptions)
        );
        
        if ($this->delegates->contains($data)) {
            throw new \InvalidArgumentException(
                'Another delegate is already registered with same parameters');
        }
        
        $this->delegates->insert($data, $priority);
        
        if (is_string($delegate) && class_exists($delegate)) {
            $this->plugins->setInvokableClass($delegate, $delegate);
        }
    }

    /**
     * Access to delegate plugin manager
     *
     * Note that new delegates cannot be registered directly via
     * plugin manager.
     *
     * @return \Foaf\InputFilter\Configurator\DelegateManager
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
     * @return \Foaf\InputFilter\Configurator
     */
    public function setDefaultInputFilterClass($class)
    {
        if (! class_exists($class)) {
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
        if (! $this->factory) {
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
     * Retrieve input filter specifications
     *
     * @param string $name            
     * @param arrayÊ $delegates            
     * @return array
     */
    protected function internalGetSpecifications($name, array $delegates, 
            array & $references)
    {
        $specs = array();
        
        // Merge specifications provided by delegates
        foreach ($delegates as $delegate) {
            
            $ext = $delegate->getInputFilterSpecifications($this, $name);
            
            if (is_array($ext)) {
                $specs = ArrayUtils::merge($specs, $ext);
            }
        }
        
        // Add parent reference to this
        if (isset($specs['type']) 
            && is_string($specs['type']) 
            && !class_exists($specs['type'])) {
            
            $references[$specs['type']][] = $name;
        }
        
        // Look for child references
        foreach ($specs as $name => $input) {
            if (isset($input['type']) 
                && is_string($input['type']) 
                && !class_exists($input['type'])) {
                
                $references[$input['type']][] = $name;
            }
        }
        
        $specs = new ArrayObject($specs);
        
        // Let delegates prepare the specifications
        foreach ($delegates as $delegate) {
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
        $queue = clone $this->delegates;
        
        foreach ($queue as $data) {
            
            if ($data['name'] == $name || $data['name'] === null) {
                
                if (is_string($data['delegate'])) {
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
        if (in_array($name, $reloaded)) {
            return;
        }
        
        if (isset($this->inputFilters[$name])) {
            unset($this->inputFilters[$name]);
            unset($this->inputFilterHashes[$name]);
        }
        
        // Remove from cache
        if ($this->getCache()) {
            $this->getCache()->removeItem($this->getCacheId($name));
        }
        
        // Mark reloaded
        $reloaded[] = $name;
        
        // Fetch references and reload each
        foreach ($this->getReferences($name) as $name) {
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
        
        foreach ($this->references as $key => $refs) {
            $refKey = array_search($name, $refs);
            
            if ($refKey !== false) {
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
        
        foreach ($references as $name => $refs) {
            if (! isset($this->references[$name])) {
                $this->references[$name] = $refs;
                $this->dirtyReferences = true;
            } else {
                foreach ($refs as $refName) {
                    if (! in_array($refName, $this->references[$name])) {
                        $this->references[$name][] = $refName;
                        $this->dirtyReferences = true;
                    }
                }
            }
        }
    }

    /**
     * Flush reference specs in cache
     */
    protected function flushReferenceCache()
    {
        if ($this->getCache() && is_array($this->references)) {
            $cacheId = $this->getCacheId('references');
            
            if ($this->getCache()->hasItem($cacheId)) {
                $this->getCache()->replaceItem($cacheId, $this->references);
            } else {
                $this->getCache()->addItem($cacheId, $this->references);
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
        if (!$this->references 
            && $this->getCache()) {
            
            $this->references = $this->getCache()->getItem(
                $this->getCacheId('references')
            );
            
            if (! is_array($this->references)) {
                $this->references = array();
            }
        } else 
            if (! $this->references) {
                $this->references = array();
            }
        
        if ($name) {
            return isset($this->references[$name]) ? $this->references[$name] : array();
        } else {
            return $this->references;
        }
    }

    /**
     * Cache current references (only when changed)
     */
    protected function cacheReferences()
    {
        if ($this->getCache() && $this->references && $this->dirtyReferences) {
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
    public function setCache(StorageInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Retrieve cache
     *
     * @return \Zend\Cache\Storage\StorageInterface
     */
    public function getCache()
    {
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