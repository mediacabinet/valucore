<?php
namespace Foaf\Service\Plugin;

use Foaf\Service\Broker;
use Foaf\Service\Feature;
use Foaf\Service\ServiceInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;

class PluginManager extends AbstractPluginManager
{
    /**
     * Default set of plugins
     *
     * @var array
     */
    protected $invokableClasses = array(
        'acl'  => 'Foaf\Service\Plugin\Acl',
        'auth'  => 'Foaf\Service\Plugin\Auth',
    );
    
    /**
     * Service broker
     * 
     * @var Broker
     */
    private $serviceBroker;
    
    /**
     * Service instance
     * 
     * @var ServiceInterface
     */
    private $service;

    /**
     * Constructor
     *
     * After invoking parent constructor, add an initializer to inject the
     * attached controller, if any, to the currently requested plugin.
     *
     * @param  null|ConfigInterface $configuration
     */
    public function __construct(ConfigInterface $configuration = null)
    {
        parent::__construct($configuration);

        $this->addInitializer(
            array($this, 'injectServiceBroker')
        );
    }
    
    public function get($name, $usePeeringServiceManagers = true)
    {
        $plugin = parent::get($name, $usePeeringServiceManagers);
        $this->injectService($plugin);
        return $plugin;
    }
    
    /**
     * Set service instance
     * 
     * @param ServiceInterface $service
     */
    public function setFoafService(ServiceInterface $service)
    {
        $this->service = $service;
    }
    
    /**
     * Retrieve service instance
     * 
     * @return \Foaf\Service\ServiceInterface
     */
    public function getFoafService()
    {
        return $this->service;
    }

    /**
     * Set service
     *
     * @param  ServiceInterface $service
     * @return PluginManager
     */
    public function setServiceBroker(Broker $service)
    {
        $this->serviceBroker = $service;
        return $this;
    }

    /**
     * Retrieve service instance
     *
     * @return null|ServiceInterface
     */
    public function getServiceBroker()
    {
        return $this->serviceBroker;
    }
    
    /**
     * Inject a plugin instance with the registered 
     * service instance
     * 
     * @param unknown_type $plugin
     */
    public function injectService($plugin)
    {
        if (!is_object($plugin) || !$this->getFoafService()) {
            return;
        }
        
        if($plugin instanceof PluginInterface){
            $plugin->setService($this->getFoafService());
        }
    }

    /**
     * Inject a plugin instance with the registered 
     * service broker instance
     *
     * @param  object $plugin
     * @return void
     */
    public function injectServiceBroker($plugin)
    {
        if (!is_object($plugin)) {
            return;
        }

        if($plugin instanceof Feature\ServiceBrokerAwareInterface){
            $plugin->setServiceBroker($this->getServiceBroker());
        }
    }

    /**
     * Validate the plugin
     *
     * Any plugin is considered valid in this context.
     *
     * @param  mixed $plugin
     * @return true
     * @throws Exception\InvalidPluginException
     */
    public function validatePlugin($plugin)
    {
        return;
    }
}
