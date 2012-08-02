<?php
namespace Foaf\Service\Response;

use Foaf\Service\Response,
	Zend\Stdlib\Message;

abstract class AbstractResponse extends Message implements Response{
    
    /**
     * (non-PHPdoc)
     * @see Foaf\Service.Response::__toString()
     */
	public function __toString(){
	    return $this->content;
	}
}