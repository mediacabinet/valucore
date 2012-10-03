<?php
namespace Valu\Service\Response;

class Http extends \Zend\Http\Response implements \Valu\Service\Response{
    
    /**
     * (non-PHPdoc)
     * @see Valu\Service.Response::__toString()
     */
    public function __toString()
    {
        return $this->toString();
    }    
}