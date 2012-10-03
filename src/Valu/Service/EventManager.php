<?php
namespace Valu\Service;

use Zend\EventManager\ResponseCollection;
use Zend\EventManager\EventInterface;
use Valu\Service\Exception;

class EventManager extends \Zend\EventManager\EventManager
{

    /**
     * (non-PHPdoc)
     * 
     * @see \Zend\EventManager\EventManager::triggerListeners()
     */
    protected function triggerListeners($event, EventInterface $e, $callback = null)
    {
        $responses = new ResponseCollection();
        $listeners = $this->getListeners($event);
        
        // Add shared/wildcard listeners to the list of listeners,
        // but don't modify the listeners object
        $sharedListeners = $this->getSharedListeners($event);
        $sharedWildcardListeners = $this->getSharedListeners('*');
        $wildcardListeners = $this->getListeners('*');
        
        if (count($sharedListeners) || count($sharedWildcardListeners) || count($wildcardListeners)) {
            $listeners = clone $listeners;
        }
        
        // Shared listeners on this specific event
        $this->insertListeners($listeners, $sharedListeners);
        
        // Shared wildcard listeners
        $this->insertListeners($listeners, $sharedWildcardListeners);
        
        // Add wildcard listeners
        $this->insertListeners($listeners, $wildcardListeners);
        
        if ($listeners->isEmpty()) {
            return $responses;
        }
        
        $exception = null;
        
        foreach ($listeners as $listener) {
            
            try{
                $response  = call_user_func($listener->getCallback(), $e);
                $exception = null;
            } catch(Exception\SkippableException $ex) {
                
                if (!$exception) {
                    $exception = $ex;
                }
                
                if ($e->propagationIsStopped()) {
                    $responses->setStopped(true);
                    break;
                } else {
                    continue;
                }
            }
            
            // Trigger the listener's callback, and push its result onto the
            // response collection
            $responses->push($response);
            
            // If the event was asked to stop propagating, do so
            if ($e->propagationIsStopped()) {
                $responses->setStopped(true);
                break;
            }
            
            // If the result causes our validation callback to return true,
            // stop propagation
            if ($callback && call_user_func($callback, $responses->last())) {
                $responses->setStopped(true);
                break;
            }
        }
        
        if ($exception && $responses->isEmpty()) {
            throw $exception;
        }
        
        return $responses;
    }
}