<?php
namespace Valu\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin,
	Zend\Stdlib\Dispatchable,
	Valu\Service\Broker,
	Zend\ServiceManager\ServiceLocatorAwareInterface,
	Zend\ServiceManager\ServiceLocatorInterface;

class ServiceBroker extends AbstractPlugin{
    
    protected $locator;
    
    protected $broker;
    
    public function __construct(Broker $broker = null)
    {
        if($broker !== null){
        	$this->setBroker($broker);
        }
    }
    
    public function service($name){
        return $this->getBroker()->service($name);
    }
    
    public function getBroker()
    {
        if(!$this->broker){
            $locator = $this->getLocator();
            $this->setBroker($locator->get('ServiceBroker'));
        }
        
        return $this->broker;
    }
    
    public function setBroker(Broker $broker)
    {
        $this->broker = $broker;
    }
    
    /**
     * Get the locator
     *
     * @return Locator
     * @throws Exception\DomainException if unable to find locator
     */
    protected function getLocator()
    {
    	if ($this->locator) {
    		return $this->locator;
    	}
    	
    	$controller = $this->getController();
    
    	if (!$controller instanceof ServiceLocatorAwareInterface) {
    		throw new \Exception('ServiceBroker plugin requires controller implements ServiceLocatorAwareInterface');
    	}
    	
    	$locator = $controller->getServiceLocator();
    	
    	if (!$locator instanceof ServiceLocatorInterface) {
    		throw new \Exception('ServiceBroker plugin requires controller implements ServiceLocatorInterface');
    	}
    	
    	$this->locator = $locator;
    	return $this->locator;
    }
}