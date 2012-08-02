<?php
namespace Foaf\Service\Definition;

use Foaf\Service\Feature\InvokableInterface;
use Foaf\Service\ServiceEvent;
use Foaf\Service\Exception;
use Foaf\Service\Feature\DefinitionProviderInterface;
use Foaf\Service\Feature\EventAwareInterface;
use Zend\Cache\Storage\Adapter\AdapterInterface as CacheAdapter;

class Mapper
    implements InvokableInterface
{
    
    /**
     * Service
     * 
     * @var \Foaf\Service\Feature\DefinitionProviderInterface
     */
    private $service;
    
    /**
     * Cache adapter
     * 
     * @var Zend\Cache\Storage\Adapter\AdapterInterface
     */
    private $cacheAdapter;
    
    public function __construct(DefinitionProviderInterface $service, CacheAdapter $cacheAdapter){
        $this->service = $service;
        $this->cacheAdapter = $cacheAdapter;
    }
    
    public function __invoke(ServiceEvent $e)
    {
        $definition = $this->fetchDefinition($e->getContext());
        
        if ($definition->hasOperation($e->getOperation())) {
            throw new Exception\OperationNotFoundException(
                'Service '.get_class($this->service)." doesn't implement operation ".$e->getOperation()
            );
        }
        
        /**
         * Apply event
         */
        if($this->service instanceof EventAwareInterface){
            $this->service->setEvent($e);
        }
        
        $operation = $e->getOperation();
        $operationDef = $definition->defineOperation($operation);
        
        /**
         * Test that current context is within supported contexts
         */
        if(isset($operationDef['contexts'])){
            $contexts = preg_split('# +#', $operationDef['contexts']);
            
            if(!in_array($e->getContext(), $contexts)){
                throw new Exception\UnsupportedContextException(
                    "Operation {$operation} is not supported in '{$e->getContext()}' service context"
                ); 
            }
        }
        
        $params = $this->resolveParams($definition, $operation, $e->getParams());
        
        switch (count($params)) {
            case 0:
                $response = $this->service->{$operation}();
                break;
            case 1:
                $response = $this->service->{$operation}($params[0]);
                break;
            case 2:
                $response = $this->service->{$operation}($params[0], $params[1]);
                break;
            case 3:
                $response = $this->service->{$operation}($params[0], $params[1], $params[2]);
                break;
            case 4:
                $response = $this->service->{$operation}($params[0], $params[1], $params[2], $params[3]);
                break;
            case 5:
                $response = $this->service->{$operation}($params[0], $params[1], $params[2], $params[3], $params[4]);
                break;
            default:
                $response = call_user_func_array(
                    array($this, $operation),
                    $params
                );
                
                break;
        }

        return $response;
    }
    
    public function getService(){
        return $this->service;
    }
    
    private function fetchDefinition($context)
    {
        $class   = get_class($this->service);
        $version = $class::getVersion();
        $definition = null;
        
        /**
         * Fetch from cache
         */
        if($this->cacheAdapter && $this->cacheAdapter->hasItem($class)){
            $definition = $this->cacheAdapter->getItem($class);
        
            if($definition->getVersion() !== $version){
                $definition = null;
            }
        }
        
        if(is_null($definition)){
            $definition = $this->service->define($context);
            $definition->setVersion($version);
        
            /**
             * Cache definition
             */
            if($this->cacheAdapter){
                $this->cacheAdapter->setItem($class, $definition);
            }
        }
        
        return $definition;
    }
    
    private function resolveParams(DefinitionProviderInterface $definition, $operation, $args){
    
        $operation = $definition->defineOperation($operation);
        $resolved  = array();
    
        foreach($operation['params'] as $name => $specs)
        {
            if(array_key_exists($name, $args)){
                $resolved[$name] = $args[$name];
                unset($args[$name]);
            }
            else if($specs['has_default_value']){
                $resolved[$name] = $specs['default_value'];
            }
            else if(!$specs['is_optional']){
                throw new Exception\MissingParameterException('Parameter '.$name.' is missing');
            }
        }
    
        if(sizeof($args)){
            $args = array_keys($args);
            throw new Exception\UnknownParameterException('Unknown parameter '.array_shift($args));
        }
    
        return $resolved;
    }
}