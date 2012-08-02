<?php
namespace Foaf\Filter;

use Zend\Filter\FilterInterface;

class StringToArray implements FilterInterface{
    
    protected $separators = array(',',' ');
    
    protected $re = '/[, ]/';
    
    public function __construct($options = null)
    {
        if ($options instanceof \Zend\Config\Config) {
            $options = $options->toArray();
        }
    
        if(is_array($options)){
            if (array_key_exists('separators', $options)) {
                $this->setSeparators($options['separators']);
            }
        }
    }
    
    /**
     * Set separators
     * 
     * @param array $separators
     * @return UriComponent
     */
    public function setSeparators(array $separators){
        $this->separators = $separators;
        
        $this->re = '/[';
        $this->re .= preg_quote(implode('', $separators), '/');
        $this->re .= ']/';
        
        return $this;
    }
    
    /**
     * Retrieve separator
     * 
     * @return string
     */
    public function getSeparators(){
        return $this->separators;
    }
    
    /**
     * Filter string input to array, splitting the
     * input using configured separators
     * 
     * @param string $value
     * @return mixed
     */
	public function filter($value){
	    
	    if(is_array($value)){
	        return $value;
	    }
		
	    return preg_split($this->re, (string) $value);
	}
}