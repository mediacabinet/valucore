<?php
namespace Valu\Service\Response;

use Valu\Service\Response,
	Zend\Stdlib\Message;

abstract class AbstractResponse extends Message implements Response{
    
    /**
     * (non-PHPdoc)
     * @see Valu\Service.Response::__toString()
     */
	public function __toString(){
	    return $this->content;
	}
}