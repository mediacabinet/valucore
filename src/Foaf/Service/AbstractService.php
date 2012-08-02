<?php
namespace Foaf\Service;

use Zend\EventManager\EventInterface;
use Foaf\Service\ServiceInterface;
use Foaf\Service\ServiceEvent;
use Foaf\Service\Exception;
use Foaf\Service\Feature;
use Foaf\Service\Invoker\InvokerInterface;
use Foaf\Service\Definition;
use Foaf\Service\Definition\Driver\PhpDriver;
use Foaf\Service\Definition\DriverInterface;

/**
 * @foaf\service\ignore
 */
abstract class AbstractService 
    implements ServiceInterface, Feature\InvokerAwareInterface, Feature\ConfigurableInterface, Feature\DefinitionProviderInterface
{
    
    /**
     * Options class name
     * 
     * @var string
     */
    protected $optionsClass = 'Foaf\Service\ServiceOptions';
    
    /**
     * Options
     * 
     * @var \Zend\Stdlib\AbstractOptions
     */
    private $options = null;
	
	/**
	 * Service definition
	 * 
	 * @var Definition
	 */
	private $definition = null;
	
	/**
	 * Definition driver
	 * 
	 * @var DriverInterface
	 */
	private $definitionDriver;
	
	/**
	 * Invoker
	 *
	 * @var InvokerInterface
	 */
	private $invoker;
	
	/**
	 * Static definition driver
	 * 
	 * @var DriverInterface
	 */
	private static $sharedDefinitionDriver;
	
	public function __invoke(ServiceEvent $e)
	{
	    return $this->getInvoker()->invoke($this, $e);
	}
	
	/**
	 * Retrieve invoker instance
	 * 
	 * @return InvokerInterface
	 * @foaf\service\ignore
	 */
	public function getInvoker(){
	    
	    if(!$this->invoker){
	        $this->invoker = new \Foaf\Service\Invoker\DefinitionBased();
	    }
	    
	    return $this->invoker;
	}
	
	/**
	 * Set invoker
	 * 
	 * @param InvokerInterface $invoker
	 */
	public function setInvoker(InvokerInterface $invoker){
	    $this->invoker = $invoker;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Foaf\Service\ServiceInterface::define()
	 */
	public function define()
	{
	    if($this->definition == null){
	        $driver = $this->getDefinitionDriver();
	        $this->definition = $driver->define(get_class($this));
	    }

	    return $this->definition;
	}
	
	/**
	 * Retrieve current driver used for parsing
	 * service definitions
	 * 
	 * @return DriverInterface
	 */
	public function getDefinitionDriver()
	{
	    if($this->definitionDriver == null){
	        $this->setDefinitionDriver(
	            self::getSharedDefinitionDriver()
            );
	    }
	    
	    return $this->definitionDriver;
	}
	
	/**
	 * Set current driver used for parsing service
	 * definitions
	 * 
	 * @param DriverInterface $driver
	 */
	public function setDefinitionDriver(DriverInterface $driver)
	{
	    $this->definitionDriver = $driver;
	}
	
	/**
	 * Set options as config file, array or traversable
	 * object
	 * 
	 * @param string|array|\Traversable $config
	 */
	public function setConfig($config)
	{
		if(is_string($config) && file_exists($config)){
			$config = \Zend\Config\Factory::fromFile($config);
		}
		
		if(!is_array($config) && !($config instanceof \Traversable)){
		    throw new \InvalidArgumentException('Config must be an array, Traversable object or filename');
		}
	
		$this->setOptions($config);
	}
	
    /**
     * Set service options
     *
     * @param  array|Traversable $options
     * @return AbstractService
     */
	public function setOptions($options)
    { 
    	$this->options = new $this->optionsClass($options);
        return $this;
    }
    
	/**
     * Retrieve service options
     *
     * @return array
     */
    public function getOptions()
    {
        if(!$this->options){
            $this->options = new $this->optionsClass(array());
        }
        
        return $this->options;
    }

    /**
     * Is an option present?
     *
     * @param  string $key
     * @return bool
     */
    public function hasOption($key)
    {
        return $this->getOptions()->__isset($key);
    }
    
    /**
     * Set option
     * 
     * @param string $key
     * @param mixed $value
     * @return AbstractService
     */
    public function setOption($key, $value)
    {
    	$this->getOptions()->__set($key, $value);
    	return $this;
    }

    /**
     * Retrieve a single option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->getOptions()->__get($key);
    }
    
    /**
     * Retrieve shared definition driver
     * 
     * @return DriverInterface
     */
    public static function getSharedDefinitionDriver()
    {
        if(self::$sharedDefinitionDriver == null){
            self::$sharedDefinitionDriver = new PhpDriver();
        }
         
        return self::$sharedDefinitionDriver;
    }
}