<?php
namespace Foaf\Service\Response\Http;

use Foaf\Service\Response\Http;

/**
 * Binary HTTP service response
 * 
 * @author juhasuni
 *
 */
class Binary extends Http{
    
    /**
     * (non-PHPdoc)
     * @see Zend\Stdlib.Message::setContent()
     */
    public function setContent($value){
        return $this->setBytes($value);
    }
    
    /**
     * Set reference to byte stream
     *  
     * @param string $bytes
     * @return Binary
     */
    public function setBytes(&$bytes){
        $this->content =& $bytes;
        return $this;
    }
}