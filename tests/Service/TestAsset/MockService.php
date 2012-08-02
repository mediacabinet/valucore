<?php
namespace Foaf\Test\Service\TestAsset;

use Foaf\Service\ServiceInterface;
use Foaf\Service\Definition\DriverInterface as DefinitionDriver;
use Foaf\Service\ServiceEvent;

/**
 * Mock service
 * 
 * @version 1.0
 */
class MockService extends AbstractService implements ServiceInterface
{
    
    protected $definitionDriver;
    
    public function __invoke(ServiceEvent $e){
        return true;
    }
    
    /**
     * Set definition driver
     * 
     * @foaf\service\ignore
     */
    public function setDriver(DefinitionDriver $driver){
        $this->definitionDriver = $driver;
    }
    
    /**
     * @foaf\service\ignore
     */
    public function define(){
        return $this->definitionDriver->define(get_class($this));
    }
    
    /**
     * @foaf\service\ignore
     */
    public function setConfig($config)
    {}
    
    public function find($query, $default = null)
    {
        return 1;  
    }
    
    public function delete($id){
        return true;
    }
    
    public function deleteAll(){
        return true;
    }
    
    public function create($name, array $specs){
        return 1;
    }
    
    /**
     * @foaf\service\ignore
     */
    public function nonServiceMethod()
    {}
    
    protected function internalProtected()
    {} 
}