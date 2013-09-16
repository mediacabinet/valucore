<?php
namespace Valu\Model\Filter;

use Valu\Model\ResolverInterface;
use Zend\Filter\AbstractFilter;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\AbstractPluginManager;

/**
 * Filter that resolves model instances from abritary
 * data using injected resolver instance
 */
class Resolver 
    extends AbstractFilter
    implements ServiceLocatorAwareInterface
{
    /**
     * Resolver instance
     * 
     * @var \Viso\Model\ResolverInterface
     */
    private $resolver;
    
    /**
     * Should the input be treated as an array?
     * 
     * @var boolean
     */
    private $multi = false;
    
    /**
     * Name of the model
     * 
     * @var string
     */
    private $modelName;
    
    /**
     * Name of the resolver
     * 
     * @var string
     */
    private $resolverName;
    
    /**
     * Service locator instance
     * 
     * @var ServiceManager
     */
    private $serviceLocator;
    
    public function __construct($options = null)
    {
        if (is_array($options) || $options instanceof \ArrayAccess) {
            $this->setOptions($options);
        }
    }
    
    /**
     * @see \Zend\Filter\FilterInterface::filter()
     */
    public function filter($value)
    {
        if ($this->multi) {
            
            if (is_array($value) || $value instanceof \Traversable) {
                
                if ($value instanceof \ArrayAccess) {
                    $filtered = $value;
                } else {
                    $filtered = array();
                }
                
                foreach ($value as $key => $val) {
                    $filtered[$key] = $this->resolveModel($val);
                }
                
                return $filtered;
            } else {
                return array();
            }
        } else {
            return $this->resolveModel($value);
        }
    }
    
    /**
     * Resolve model
     * 
     * @param mixed $data
     * @return object|null
     */
    public function resolveModel($data)
    {
        $resolver = $this->getResolver();
        
        if (!$resolver) {
            throw new \RuntimeException(
                'Resolver is not set or configured properly');
        }
        
        return $resolver->resolve($this->getModelName(), $data);
    }

    /**
     * Should the filter resolve multiple instances?
     * 
     * @return boolean
     */
    public function getMulti()
    {
        return $this->multi;
    }
    
    /**
     * Set filter to resolve multiple instances
     * 
     * @param boolean $multi
     */
    public function setMulti($multi)
    {
        $this->multi = $multi;
    }
    
    /**
     * Set name of the resolver
     * 
     * @param string $name
     */
    public function setResolverName($name)
    {
        $this->resolverName = $name;
    }
    
    /**
     * Retrieve name of the resolver
     * 
     * @return string
     */
    public function getResolverName()
    {
        return $this->resolverName;
    }
    
    /**
     * Retrieve name of the model to resolve
     * 
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }
    
    /**
     * Set model name
     * 
     * @param string $name
     */
    public function setModelName($name)
    {
        $this->modelName = $name;
    }
    
    public function setResolver(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }
    
    /**
     * Retrieve model resolver
     *
     * @return \Viso\Model\ResolverInterface
     */
    public function getResolver()
    {
        if (!$this->resolver && $this->getServiceLocator() && $this->getResolverName()) {
            
            if ($this->getServiceLocator() instanceof AbstractPluginManager) {
                
                // Fetch main service locator
                $serviceLocator = $this->getServiceLocator()->getServiceLocator();
                
                $resolver = $serviceLocator->get($this->getResolverName());
                $this->setResolver($resolver);
            }
            
        }
    
        return $this->resolver;
    }
    
    public function setResolved(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }
    
    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}