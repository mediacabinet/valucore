<?php
namespace Foaf\Model;

use Zend\InputFilter\InputFilter;

trait InputFilterTrait
{
    /**
     * Input filter
     *
     * @var \Zend\InputFilter\InputFilter
     */
    protected $inputFilter;
    
    /**
     * Default input filter
     * 
     * @var \Zend\InputFilter\InputFilter
     */
    protected static $defaultInputFilter;
    
    /**
     * Set input filter
     *
     * @param InputFilter $inputFilter
     */
    public function setInputFilter(InputFilter $inputFilter){
        $this->inputFilter = $inputFilter;
    }
    
    /**
     * Retrieve input filter and initialize
     * with given data
     *
     * @param array $data
     * @return \Zend\InputFilter\InputFilter
     */
    public function getInputFilter($data = array()){
        if(!$this->inputFilter){
            if(self::getDefaultInputFilter()){
                $this->setInputFilter(self::getDefaultInputFilter());
            }
            else{
                return null;
            }
        }

        $this->inputFilter->setData($data);
         
        return $this->inputFilter;
    }
    
    /**
     * Set default input filter
     *
     * @param InputFilter $inputFilter
     */
    public static function setDefaultInputFilter(InputFilter $inputFilter)
    {
        self::$defaultInputFilter = $inputFilter;
    }
    
    /**
     * Retrieve default input filter
     *
     * @return \Zend\InputFilter\InputFilter
     */
    public static function getDefaultInputFilter()
    {
        return self::$defaultInputFilter;
    }
    
    /**
     * Filter and set value of a property
     *
     * @param string $property
     * @param mixed $value
     * @throws InvalidArgumentException
     * @return \stdClass
     */
    protected function setProp($property, $value){

        if($this->getInputFilter()){
            $this->{$property} = $this->filterProp($property, $value);
        }
        else{
            $this->{$property} = $value;
        }
    
        return $this;
    }
    
    /**
     * Filter value according to filter settings for given
     * property name
     *
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    protected function filterProp($property, $value){
        
        $filter = $this->getInputFilter(array($property => $value));
        
        if($filter && $filter->has($property)){
            return $filter->getValue($property);
        }
        else{
            return $value;
        }
    }
}