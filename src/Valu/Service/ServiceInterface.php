<?php
namespace Valu\Service;

interface ServiceInterface
{
    /**
     * Invoke operation based on event
     * 
     * @param ServiceEvent $e
     */
    public function __invoke(ServiceEvent $e);
    
    /**
     * Retrieve current event instance
     * 
     * @return ServiceEvent
     */
    public function getEvent();
}