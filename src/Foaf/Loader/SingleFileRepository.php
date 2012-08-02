<?php
namespace Foaf\Loader;

use Zend\Loader\SplAutoloader;

class SingleFileRepository implements SplAutoloader
{
	const NS_SEPARATOR     	= '\\';
	const LOAD_NS 			= 'namespaces';
	
	protected $namespaces = array();
	
    /**
     * Constructor
     *
     * @param  null|array|Traversable $options
     * @return void
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setOptions($options);
        }
    }
    
    public function setOptions($options)
    {
        if (!is_array($options) && !($options instanceof \Traversable)) {
            throw new \Zend\Loader\Exception\InvalidArgumentException('Options must be either an array or Traversable');
        }

        foreach ($options as $type => $pairs) {
            switch ($type) {
                case self::LOAD_NS:
                    if (is_array($pairs) || $pairs instanceof \Traversable) {
                        $this->registerNamespaces($pairs);
                    }
                    break;
                default:
                    // ignore
            }
        }
        
        return $this;
    }
    
    /**
     * Register a namespace/directory pair
     *
     * @param  string $namespace
     * @param  string $directory
     * @return StandardAutoloader
     */
    public function registerNamespace($namespace, $file)
    {
        $namespace = rtrim($namespace, self::NS_SEPARATOR). self::NS_SEPARATOR;
        $this->namespaces[$namespace] = $file;
        return $this;
    }

    /**
     * Register many namespace/directory pairs at once
     *
     * @param  array $namespaces
     * @return StandardAutoloader
     */
    public function registerNamespaces($namespaces)
    {
        if (!is_array($namespaces) && !$namespaces instanceof \Traversable) {
            throw new \Zend\Loader\Exception\InvalidArgumentException('Namespace pairs must be either an array or Traversable');
        }

        foreach ($namespaces as $namespace => $directory) {
            $this->registerNamespace($namespace, $directory);
        }
        return $this;
    }
    
    /**
     * Register the autoloader with spl_autoload
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register(array($this, 'autoload'));
    }
    
    /**
     * Defined by Autoloadable; autoload a class
     *
     * @param  string $class
     * @return false|string
     */
    public function autoload($class)
    {
        if (false !== strpos($class, self::NS_SEPARATOR)) {
        	
	        foreach ($this->namespaces as $leader => $file) {
	            if (0 === strpos($class, $leader)) {
	                if (file_exists($file)) {
	                    return include $file;
	                }
	                
	                return false;
	            }
	        }
        }
        
        return false;
    }
}