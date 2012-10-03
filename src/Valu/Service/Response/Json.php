<?php
namespace Valu\Service\Response;

class Json extends AbstractResponse{
    
    protected $dirty = false;
    
    protected $json = '';
    
    public function setContent($value)
    {
        if($value !== $this->content){
            $this->dirty = true;
        }
        
        return parent::setContent($value);
    }
    
    public function getJson()
    {
        if($this->dirty){
        	$this->json = \Zend\Json\Json::encode($this->getContent());
        }
        
        return $this->json;
    }
    
    public function __toString()
    {
        return $this->getJson();
    }    
}