<?php
namespace Valu\Auth\Service;

use Zend\Mvc\MvcEvent;

use Zend\Authentication\Result;
use Valu\Service\AbstractService;

class StaticAuth extends AbstractService
{
    public static function version()
    {
        return '1.0';
    }

    public function getIdentity()
    {
        return $this->getOption('identity');
    }
    
    public function clearIdentity()
    {
        $this->identity = null;
    }
    
    public function authenticate(MvcEvent $event)
    {
        
        if ($this->getIdentity()) {
            $result = new Result(Result::SUCCESS, $this->getIdentity());
        } else {
            $result = new Result(Result::FAILURE);
        }
        
        return $result;
    }
    
    public function setOption($key, $value)
    {
        parent::setOption($key, $value);
    }
}