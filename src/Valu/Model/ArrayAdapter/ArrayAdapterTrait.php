<?php
namespace Valu\Model\ArrayAdapter;

use Valu\Model\ArrayAdapter;

trait ArrayAdapterTrait
{
    
    /**
     * Array adapter
     * 
     * @var \Valu\Model\ArrayAdapter;
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
            $this->arrayAdapter = self::getDefaultArrayAdapter();
        }
    
        return $this->arrayAdapter;
    }
    
    /**
     * Retrieve default array adapter instance
     * 
     * @return \Valu\Model\ArrayAdapter
     */
    public static function getDefaultArrayAdapter()
    {
        if (isset(static::$defaultArrayAdapter)) {
            return static::$defaultArrayAdapter;
        } else {
            return ArrayAdapter::getSharedInstance();
        }
    }
    
    /**
     * Set default array adapter instance
     * 
     * @param \Valu\Model\ArrayAdapter $arrayAdapter 
     */
    public static function setDefaultArrayAdapter(ArrayAdapter $arrayAdapter)
    {
        static::$defaultArrayAdapter = $arrayAdapter;
    }
}