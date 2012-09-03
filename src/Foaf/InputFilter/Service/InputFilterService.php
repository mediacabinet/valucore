<?php
namespace Foaf\InputFilter\Service;

use Foaf\InputFilter\InputFilterRepository;
use Foaf\Service\Exception\OperationNotFoundException;
use Foaf\Service\ServiceInterface;
use Foaf\Service\ServiceEvent;

class InputFilterService
    implements ServiceInterface
{
    /**
     * Input filter repository
     * 
     * @var \Foaf\InputFilter\InputFilterRepository
     */
    protected $repository;
    
    /**
     * Service event
     * 
     * @var ServiceEvent
     */
    protected $event;
    
    public static function version()
    {
        return '0.1';
    }
    
    public function __construct(InputFilterRepository $inputFilterRepository)
    {
        $this->repository = $inputFilterRepository;
    }
    
    public function __invoke(ServiceEvent $e)
    {
        $this->event = $e;
        
        switch($e->getOperation()){
            case 'reload':
                $this->reload($e->getParam('name', $e->getParam(0)));
                break;
            case 'get':
                return $this->get($e->getParam('name', $e->getParam(0)));
                break;
            default:
                throw new OperationNotFoundException(
                    sprintf("Service doesn't implement operation %s", $e->getOperation())
                );
                break;
        }
    }
    
    public function getEvent()
    {
        return $this->event;
    }
    
    public function get($name)
    {
        return $this->repository->get($name);
    }
    
    public function reload($name)
    {
        return $this->repository->reload($name);
    }
}