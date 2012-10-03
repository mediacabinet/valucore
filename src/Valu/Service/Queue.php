<?php
namespace Valu\Service;

class Queue{
    
    /**
     * Service broker
     * 
     * @var Broker
     */
    protected $serviceBroker;
    
    public function __construct(Broker $serviceBroker){
        $this->serviceBroker = $serviceBroker;
    }
	
    /**
     * Adds service operation to queue
     * 
     * @param string $id 				Operation ID
     * @param int|null $priority 		Operation priority, when set NULL, the operation is executed immediately
     * @param string $service			Name of the service
     * @param string $operation			Name of the operation
     * @param array|null $args			Operation arguments
     */
    public function add($id, $priority, $service, $operation, $args = null){
        if($priority == null){
            $this->restBroker()->exec($service, $operation, $args);
        }
		else{
		    return $this->restBroker()->delay($id, $priority, $service, $operation);
		}
    }
    
    /**
     * Remove a queued operation (unless already executed)
     * 
     * @param string $id
     * @return boolean True on success
     */
    public function remove($id){
        return $this->restBroker()->remove($id);
    }
    
    /**
     * Retrieve access to RestBroker service
     * 
     * @return \ValuApp\Service\RestBroker
     */
    protected function restBroker(){
        return $this->serviceBroker->service('RestBroker');
    }
}