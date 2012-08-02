<?php
namespace Foaf\Service\Invoker;

use Foaf\Service\ServiceEvent;
use Foaf\Service\ServiceInterface;
use Foaf\Service\Exception;
use Foaf\Service\Definition;
use Foaf\Service\Invoker\InvokerInterface;
use Foaf\Service\Feature\DefinitionProviderInterface;
use Zend\Cache\Storage\StorageInterface;

class DefinitionBased implements InvokerInterface
{

    /**
     * Cache adapter
     *
     * @var Zend\Cache\Storage\Adapter\AdapterInterface
     */
    private $cacheAdapter;

    public function __construct(StorageInterface $cacheAdapter = null)
    {
        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * Invoke a method
     *
     * @param $service ServiceInterface           
     * @param $e ServiceEvent           
     * @throws \InvalidArgumentException
     * @throws Exception\OperationNotFoundException
     * @throws Exception\UnsupportedContextException
     * @return mixed
     */
    public function invoke(ServiceInterface $service, ServiceEvent $e)
    {
        
        if (! $service instanceof DefinitionProviderInterface) {
            throw new \InvalidArgumentException(
                    'Service must implement DefinitionProviderInterface');
        }
        
        $definition = $this->defineService($service);
        $operation = $e->getOperation();
        
        if (! $definition->hasOperation($operation)) {
            throw new Exception\OperationNotFoundException(
                    'Service ' . get_class($service) .
                             " doesn't provide operation " . $operation);
        }
        
        $operationDef = $definition->defineOperation($operation);
        
        /**
         * Test that current context is within supported contexts
         */
        if (isset($operationDef['contexts'])) {
            $contexts = preg_split('# +#', $operationDef['contexts']);
            
            if (! in_array($e->getContext(), $contexts)) {
                throw new Exception\UnsupportedContextException(
                        "Operation {$operation} is not supported in '{$e->getContext()}' service context");
            }
        }
        
        $params = $this->resolveParams($definition, $operation, $e->getParams());
        
        switch (count($params)) {
            case 0:
                $response = $service->{$operation}();
                break;
            case 1:
                $response = $service->{$operation}($params[0]);
                break;
            case 2:
                $response = $service->{$operation}($params[0], $params[1]);
                break;
            case 3:
                $response = $service->{$operation}($params[0], $params[1], 
                        $params[2]);
                break;
            case 4:
                $response = $service->{$operation}($params[0], $params[1], 
                        $params[2], $params[3]);
                break;
            case 5:
                $response = $service->{$operation}($params[0], $params[1], 
                        $params[2], $params[3], $params[4]);
                break;
            default:
                $response = call_user_func_array(array($this, $operation), 
                        $params);
                
                break;
        }
        
        return $response;
    }

    /**
     * Fetch service definition
     *
     * @param $service DefinitionProviderInterface           
     * @return array
     */
    private function defineService(DefinitionProviderInterface $service)
    {
        $class = get_class($service);
        $version = $class::version();
        $definition = null;
        
        /**
         * Fetch from cache
         */
        if ($this->cacheAdapter && $this->cacheAdapter->hasItem($class)) {
            $definition = $this->cacheAdapter->getItem($class);
            
            if ($definition->getVersion() !== $version) {
                $definition = null;
            }
        }
        
        if (is_null($definition)) {
            $definition = $service->define();
            $definition->setVersion($version);
            
            /**
             * Cache definition
             */
            if ($this->cacheAdapter) {
                $this->cacheAdapter->setItem($class, $definition);
            }
        }
        
        return $definition;
    }

    /**
     * Resolve parameter order for operation based on given arguments
     *
     * @param $definition DriverInterface           
     */
    private function resolveParams(Definition $definition, $operation, $args)
    {
        // Return numeric parameter list as is and let
        // PHP handle errors
        if (array_key_exists(0, $args)) {
            return $args;
        }
        
        $definition = $definition->defineOperation($operation);
        $resolved = array();
        
        foreach ($definition['params'] as $name => $specs) {
            if (array_key_exists($name, $args)) {
                $resolved[] = $args[$name];
                unset($args[$name]);
            } else 
                if ($specs['has_default_value']) {
                    $resolved[] = $specs['default_value'];
                } else 
                    if (! $specs['optional']) {
                        throw new Exception\MissingParameterException(
                                'Parameter ' . $name . ' is missing');
                    }
        }
        
        if (sizeof($args)) {
            $args = array_keys($args);
            throw new Exception\UnknownParameterException(
                    'Unknown parameter ' . array_shift($args));
        }
        
        return $resolved;
    }
}