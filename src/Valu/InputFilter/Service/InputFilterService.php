<?php
namespace Valu\InputFilter\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Valu\InputFilter\InputFilterRepository;
use Valu\Service\Exception\OperationNotFoundException;
use Valu\Service\ServiceInterface;
use Valu\Service\ServiceEvent;

class InputFilterService
    implements  ServiceInterface, 
                ServiceLocatorAwareInterface
{
    /**
     * Input filter repository
     * 
     * @var \Valu\InputFilter\InputFilterRepository
     */
    protected $repository;
    
    /**
     * Service event
     * 
     * @var ServiceEvent
     */
    protected $event;
    
    /**
     * Service locator
     *
     * @var ServiceLocatorInterface
     */
    private $serviceLocator;
    
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
                return $this->reload($e->getParam('name', $e->getParam(0)));
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
    
    /**
     * @see \Valu\Service\ServiceInterface::getEvent()
     */
    public function getEvent()
    {
        return $this->event;
    }
    
    /**
     * Retrieve input filter by name
     * 
     * @param string $name
     * @return \Zend\InputFilter\InputFilterInterface|null
     */
    public function get($name)
    {
        $inputFilter = $this->repository->get($name);
        
        if ($inputFilter && 
            $inputFilter instanceof ServiceLocatorAwareInterface && 
            !$inputFilter->getServiceLocator() &&
            $this->getServiceLocator()) {
            
            $inputFilter->setServiceLocator($this->getServiceLocator());
        }
        
        return $inputFilter;
    }
    
    /**
     * Reload input filter
     * 
     * @param string $name
     * @return boolean
     */
    public function reload($name)
    {
        return $this->repository->reload($name);
    }
    
    /**
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::getServiceLocator()
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    /**
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::setServiceLocator()
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}