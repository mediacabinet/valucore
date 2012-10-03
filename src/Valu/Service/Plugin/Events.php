<?php
namespace Valu\Service\Plugin;

class Events extends AbstractPlugin
{
    const SEPARATOR = '.';
    
    const PRE = 'pre';
    
    const POST = 'post';
    
    /**
     * Trigger pre event
     * 
     * @param string $description
     * @param array $args
     * @return ArrayObject
     */
    public function triggerPre($target = null, $args = null, $description = null)
    {
        if (!$this->getService()->getEvent()) {
            return false;
        }
        
        if($args === null){
            $args = $this->getService()->getEvent()->getParams();
        }
        
        if(is_array($args)){
            $argv = $this->prepareArgs($args);
        } else {
            $argv = new \ArrayObject();
        }
        
        $event = $this->makeEvent($description, self::PRE);
        $this->getEventManager()->trigger($event, $target, $argv);
        
        return $argv;
    }
    
    /**
     * Trigger post event
     * 
     * @param string $description
     * @param array $args
     */
    public function triggerPost($target = null, $args = null, $description = null)
    {
        if (!$this->getService()->getEvent()) {
            return false;
        }
        
        if($args === null){
            $args = $this->getService()->getEvent()->getParams();
        }
        
        $event = $this->makeEvent($description, self::POST);
        return $this->getEventManager()->trigger($event, $target, $args);
    }
    
    /**
     * Trigger an event
     * 
     * @param string $event
     * @param mixed $target
     * @param array $args
     * @return \Zend\EventManager\ResponseCollection
     */
    public function trigger($event, $target, $args)
    {
        $event = strtolower($event);
        
        return $this->getEventManager()->trigger($event, null, $args);
    }
    
    /**
     * Prepare arguments to instance of ArrayObject
     * 
     * @param mixed $args
     * @return \ArrayObject
     */
    public function prepareArgs($args)
    {
        if($args instanceof \ArrayObject){
            return $args;
        }
        else{
            return $this->getEventManager()->prepareArgs($args);
        }
    }
    
    /**
     * Retrieve event manager instance
     * 
     * @return \Zend\EventManager\EventManagerInterface
     */
    public function getEventManager()
    {
        return $this->getServiceBroker()->getEventManager();
    }
    
    protected function makeEvent($description = null, $prefix = '')
    {
        if(!$description){
            $description = $this->getService()->getEvent()->getOperation();
        }
        
        $service = $this->getService()->getEvent()->getService();
        $event   = $service . self::SEPARATOR . $description;
        
        if($prefix){
            $event = $prefix . self::SEPARATOR . $event; 
        }
        
        return strtolower($event);
    }
}