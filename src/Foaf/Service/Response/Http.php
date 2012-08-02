<?php
namespace Foaf\Service\Response;

class Http extends \Zend\Http\Response implements \Foaf\Service\Response{
    
    /**
     * (non-PHPdoc)
     * @see Foaf\Service.Response::__toString()
     */
    public function __toString()
    {
        return $this->toString();
    }    
}