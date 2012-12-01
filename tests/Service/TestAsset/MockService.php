<?php
namespace Valu\Test\Service\TestAsset;

use Valu\Service\ServiceInterface;
use Valu\Service\Definition\DriverInterface as DefinitionDriver;
use Valu\Service\ServiceEvent;

/**
 * Mock service
 * 
 * @version 1.0
 */
class MockService extends AbstractService implements ServiceInterface
{
    
    protected $definitionDriver;
    
    protected $event;
    
    public function __invoke(ServiceEvent $e){
        $this->event = $e;
        return true;
    }
    
    public function getEvent()
    {
        return $this->event;
    }
    
    /**
     * Set definition driver
     * 
     * @valu\service\ignore
     */
    public function setDriver(DefinitionDriver $driver){
        $this->definitionDriver = $driver;
    }
    
    /**
     * @valu\service\ignore
     */
    public function define(){
        return $this->definitionDriver->define(get_class($this));
    }
    
    /**
     * @valu\service\ignore
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
     * @valu\service\ignore
     */
    public function nonServiceMethod()
    {}
    
    protected function internalProtected()
    {} 
}