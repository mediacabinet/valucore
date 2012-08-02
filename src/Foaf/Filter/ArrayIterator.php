<?php
namespace Foaf\Filter;

use Zend\Filter\FilterInterface;

class ArrayIterator implements FilterInterface{
    
    /**
     * Filter chain
     * 
     * @var \Zend\Filter\FilterChain
     */
    protected $chain;
    
    /**
     * Array of registered filters
     * 
     * @var array
     */
    protected $filters = array();
    
    /**
     * Whether to remove empty elements 
     * @var boolean
     */
    protected $removeEmpty = true;
    
    public function __construct(array $filters, $options = null)
    {
        $this->chain = new \Zend\Filter\FilterChain();
        
        if ($options instanceof \Zend\Config\Config) {
            $options = $options->toArray();
        }
        
        if(!is_array($options)){
            $options = array();
        }

        if(isset($options['remove_empty'])){
            $this->setRemoveEmpty((bool) $options['remove_empty']);
        }

        $this->setFilters($filters);
    }
    
    /**
     * Set filters
     *
     * @param array $filters
     * @return NormalizeArray
     */
    public function setFilters(array $filters){
        $this->filters = $filters;
        
        foreach ($filters as &$filter){
            if(is_string($filter)){
                $filter = array('name' => $filter);
            }
        }
        
        $this->chain->setOptions(array('filters' => $filters));

        return $this;
    }
    
    /**
     * Retrieve filter
     *
     * @return string
     */
    public function getFilters(){
        return $this->filters;
    }
    
    /**
     * Set whether empty elements should be removed
     * 
     * Element is considered empty if it's value is an empty
     * string or NULL.
     * 
     * @param boolean $remove
     */
    public function setRemoveEmpty($remove){
        $this->removeEmpty = $remove;
    }
    
    /**
     * Should empty elements be removed?
     * 
     * @return boolean
     */
    public function getRemoveEmpty(){
        return $this->removeEmpty;
    }
    
    /**
     * Normalize values in array
     * 
     * @param array $value
     * @return mixed
     */
	public function filter($value){
	    
	    if(is_array($value)){
	        
	        $value = array_map(
                array($this->chain, 'filter'), 
                $value
            );
	        
	        if($this->getRemoveEmpty()){
	            foreach($value as $key => $val){
	                if($val === '' || $val === null){
	                    unset($value[$key]);
	                }
	            }    
	        }
	        
	        return $value;
	    }
		else{
		    return array();
		}
	}
}