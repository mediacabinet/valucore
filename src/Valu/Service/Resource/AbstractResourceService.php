<?php
namespace Valu\Service;
use Valu\Service\Exception\SkippableException;
use Valu\Service\Exception\UnsupportedOperationException;
use Valu\Service\Feature;
use Valu\Service\Broker;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class AbstractResourceService implements ServiceInterface, 
        Feature\ServiceBrokerAwareInterface, ServiceLocatorAwareInterface
{

    const NS_WILDCARD = '*';

    /**
     * Resource namespace
     *
     * @var string
     */
    protected $ns;
    
    /**
     * Default proxy service name
     * 
     * @var string
     */
    protected $proxyService;

    /**
     * Proxy settings
     * 
     * Define, which operations are supported and how they are
     * mapped to another service.
     * 
     * Example of simple mapping, where operations <b>find</b> 
     * and <b>update</b> are proxied to $proxyService.
     * <code>
     * $proxySettings = [
     *     'find' => true,
     *     'update' => true
     * ];
     * 
     * Example of more complex mapping, where both service and
     * operation name are specified.
     * <code>
     * $proxySettings = [
     *     'find' => ['service' => 'Finder', 'operation' => 'doFind'],
     *     'update' => ['service' => 'Updater', 'operation' => 'doUpdate'],
     * ];
     * </code>
     * 
     * @var array
     */
    protected $proxySettings = array();

    /**
     * Service event instance
     *
     * @var Valu\Service\ServiceEvent
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
     *
     * @see \Valu\Service\ServiceInterface::__invoke()
     */
    public function __invoke(ServiceEvent $e)
    {
        $this->event = $e;
        
        $namespace = $e->getParam('ns', 0);
        
        if (! $this->isSupportedNs()) {
            throw new SkippableException("Namespace is not supported");
        }
        
        $method = $e->getOperation();
        
        if (! method_exists($this, $method)) {
            return $this->proxy($e);
        }
        
        switch ($e->getOperation()) {
            case 'create':
                return $this->{$method}($e->getParam('specs', 1));
                break;
            case 'update':
            case 'updateMany':
                return $this->{$method}(
                    $e->getParam('query', 1), 
                    $e->getParam('specs', 2));
                break;
            case 'remove':
            case 'removeMany':
                return $this->{$method}($e->getParam('query', 1));
                break;
            case 'find':
            case 'findMany':
                return $this->{$method}(
                    $e->getParam('query', 1),
                    $e->getParam('specs', 2));
                break;
        }
    }

    /**
     * Proxy request to another service
     * 
     * @param ServiceEvent $e
     * @throws UnsupportedOperationException
     * @throws \Exception
     * @return \Zend\EventManager\ResponseCollection
     */
    protected function proxy(ServiceEvent $e)
    {
        $operation = $e->getOperation();
        
        if (isset($this->proxySettings[$operation])) {
            $map = $this->proxySettings[$operation];
            
            if (!$map) {
                throw new UnsupportedOperationException(
                    sprintf(
                            "This service implementation doesn't provide operation %s",
                            $operation));
            }
            
            if (is_bool($map)) {
                $map = array('service' => $this->proxyService);
            }
            
            if (is_string($map)) {
                $map = array('service' => $map);
            }
            
            $service   = isset($map['service']) ? $map['service'] : $this->proxyService;
            $operation = isset($map['operation']) ? $map['operation'] : $operation;
            $args      = isset($map['args']) ? $map['args'] : $e->getParams();
            
            if (! $service) {
                throw new \Exception(
                    'Invalid proxy definition; missing service name');
            }
            
            return $this->service($service)
                ->exec($operation, $args);
        } else {
            throw new UnsupportedOperationException(
                sprintf(
                    "This service implementation doesn't provide operation %s", 
                    $operation));
        }
    }

    /**
     * Is namespace supported?
     *
     * @param string $ns            
     * @return boolean
     */
    protected function isSupportedNs($ns)
    {
        if ($ns == self::NS_WILDCARD) {
            return true;
        } elseif ($this->ns && $ns === $this->ns) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Quick access to service
     * 
     * @return \Valu\Service\Broker\Worker
     */
    protected function service($name)
    {
        return $this->getServiceBroker()->service($name);
    }

    /**
     *
     * @see \Valu\Service\ServiceInterface::getEvent()
     */
    public function getEvent()
    {
        return $this->event;
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
     * 
     * @see Zend\ServiceLocator\ServiceLocator::setServiceLocator()
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    /**
     * (non-PHPdoc)
     * 
     * @see \Valu\Service\Feature\ServiceBrokerAwareInterface::setServiceBroker()
     */
    public function setServiceBroker(Broker $broker)
    {
        $this->serviceBroker = $broker;
    }

    /**
     * Retrieve service broker instance
     *
     * @return \Valu\Service\Broker
     */
    public function getServiceBroker()
    {
        return $this->serviceBroker;
    }
}