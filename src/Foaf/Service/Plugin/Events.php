<?php
namespace Foaf\Service\Plugin;

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
    public function triggerPre($description, $args)
    {
        $argv = $this->prepareArgs($args);
        
        $event = $this->makeEvent($description, self::PRE);
        $this->getEventManager()->trigger($event, null, $argv);
        
        return $argv;
    }
    
    /**
     * Trigger post event
     * 
     * @param string $description
     * @param array $args
     */
    public function triggerPost($description, $args)
    {
        $event = $this->makeEvent($description, self::POST);
        return $this->getEventManager()->trigger($event, null, $args);
    }
    
    public function prepareArgs($args)
    {
        return $this->getEventManager()->prepareArgs($args);
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
    
    protected function makeEvent($description, $prefix)
    {
        $service = $this->getService()->getEvent()->getService();
        
        return $prefix . self::SEPARATOR . $service . self::SEPARATOR . $description;
    }
}