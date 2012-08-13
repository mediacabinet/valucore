<?php
namespace Foaf\Service;

use Zend\EventManager\EventInterface;
use Foaf\Service\ServiceInterface;
use Foaf\Service\ServiceEvent;
use Foaf\Service\Exception;
use Foaf\Service\Feature;
use Foaf\Service\Broker;
use Foaf\Service\Invoker\InvokerInterface;
use Foaf\Service\Plugin\PluginManager;
use Foaf\Service\Definition;
use Foaf\Service\Definition\Driver\PhpDriver;
use Foaf\Service\Definition\DriverInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * @foaf\service\ignore
 */
abstract class AbstractService 
    implements  ServiceInterface, 
                Feature\InvokerAwareInterface, 
                Feature\ConfigurableInterface, 
                Feature\DefinitionProviderInterface,
                Feature\ServiceBrokerAwareInterface,
                ServiceLocatorAwareInterface
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
	 * Service event instance
	 * 
	 * @var Foaf\Service\ServiceEvent
	 */
	private $event;
	
	/**
	 * Service locator instance
	 * 
	 * @var Zend\ServiceManager\ServiceLocatorInterface
	 */
	private $serviceLocator;
	
	/**
	 * Service broker instance
	 * 
	 * @var Broker
	 */
	private $serviceBroker;
	
	/**
	 * Plugin manager instance
	 * 
	 * @var Foaf\Service\Plugin\PluginManager
	 */
	private $pluginManager;
	
	/**
	 * Static definition driver
	 * 
	 * @var DriverInterface
	 */
	private static $sharedDefinitionDriver;
	
	public function __invoke(ServiceEvent $e)
	{
	    $this->event = $e;
	    return $this->getInvoker()->invoke($this, $e);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Foaf\Service\ServiceInterface::getEvent()
	 */
	public function getEvent()
	{
	    return $this->event;
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
	 * Retrieve service locator instance
	 *
	 * @return \Zend\ServiceManager\ServiceLocatorInterface
	 */
	public function getServiceLocator()
	{
	    return $this->serviceLocator;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Zend\ServiceLocator\ServiceLocator::setServiceLocator()
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator){
	    $this->serviceLocator = $serviceLocator;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Foaf\Service\Feature\ServiceBrokerAwareInterface::setServiceBroker()
	 */
	public function setServiceBroker(Broker $broker)
	{
	    $this->serviceBroker = $broker;
	}
	
	/**
	 * Retrieve service broker instance
	 * 
	 * @return \Foaf\Service\Broker
	 */
	public function getServiceBroker()
	{
	    return $this->serviceBroker;
	}
	
	/**
	 * Retrieve plugin manager instance
	 * 
	 * @return \Foaf\Service\Plugin\PluginManager
	 */
	public function getPluginManager()
	{
	    if(!$this->pluginManager){
	        $this->setPluginManager(new PluginManager());
	    }
	    
	    return $this->pluginManager;
	}
	
	/**
	 * Set plugin manager instance
	 * 
	 * @param PluginManager
	 * @return Foaf\Service\AbstractService
	 */
	public function setPluginManager(PluginManager $pluginManager)
	{
	    $pluginManager->setFoafService($this);
	    $pluginManager->setServiceBroker($this->getServiceBroker());
	    
	    $this->pluginManager = $pluginManager;
	    
	    return $this;
	}
	
    /**
     * Set service options
     *
     * @param  array|Traversable $options
     * @return AbstractService
     */
	public function setOptions($options)
    { 
        if (!is_array($options) && !$options instanceof \Traversable) {
            throw new \InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }
        
        foreach ($options as $key => $value){
            $this->setOption($key, $value);
        }
    	
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
     * Get plugin instance
     *
     * @param  string     $name    Name of plugin to return
     * @param  null|array $options Options to pass to plugin constructor (if not already instantiated)
     * @return mixed
     */
    public function plugin($name, array $options = null)
    {
        return $this->getPluginManager()->get($name, $options);
    }
    
    /**
     * Method overloading: return/call plugins
     *
     * If the plugin is a functor, call it, passing the parameters provided.
     * Otherwise, return the plugin instance.
     *
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    public function __call($method, array $params)
    {
        $plugin = $this->plugin($method);
        
        if (is_callable($plugin)) {
            return call_user_func_array($plugin, $params);
        }
        
        return $plugin;
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