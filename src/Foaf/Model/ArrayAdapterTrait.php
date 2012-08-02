<?php
namespace Foaf\Model;

use Foaf\Model\ArrayAdapter;

trait ArrayAdapterTrait
{
    
    /**
     * Array adapter
     * 
     * @var \Foaf\Model\ArrayAdapter;
     */
    protected $arrayAdapter;
    
    /**
     * Load model data from array
     *
     * @param array $specs
     */
    public function fromArray(array $specs, $options = null){
        $this->getArrayAdapter()->fromArray($this, $specs, $options);
    }
    
    /**
     * Fetch model data as an associative array
     *
     * @param array $properties
     */
    public function toArray($properties = null, $options = null){
        return $this->getArrayAdapter()->toArray($this, $properties, $options);
    }
    
    /**
     * Retrieve array adapter instance
     *
     * @return ArrayAdapter
     */
    public function getArrayAdapter(){
        if(is_null($this->arrayAdapter)){
            $this->arrayAdapter = ArrayAdapter::getSharedInstance();
        }
    
        return $this->arrayAdapter;
    }
}