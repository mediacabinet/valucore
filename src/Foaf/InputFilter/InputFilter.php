<?php
namespace Foaf\InputFilter;

use Zend\InputFilter\InputFilterInterface;

class InputFilter extends \Zend\InputFilter\InputFilter
{
    /**
     * Filter data
     * 
     * @param array $data
     * @param boolean $useValidationGroup
     * @param boolean $validate
     * @throws Exception\ValidationException
     * @return Ambigous <multitype:, multitype:\Zend\InputFilter\mixed multitype: >
     */
    public function filter(array $data, $useValidationGroup = false, $validate = false)
    {
        $this->setData($data);

        if($useValidationGroup){
            $this->setValidationGroupByData($data);
        }
        
        if($validate && !$this->isValid()){
            
            $messages = $this->getMessages();
            $fields   = array();
            while(is_array($messages)){
                $key      = array_shift(array_keys($messages));
                $fields[] = $key;
                $messages = $messages[$key];
            }
            
            array_pop($fields);
            
            throw new Exception\ValidationException(
                'Property "%INPUT%" is not valid: %MESSAGE%',
                array('INPUT' => implode('.', $fields), 'MESSAGE' => $messages)        
            );
        }
        
        return $this->getValues();
    }
    
    /**
     * Validate data
     * 
     * @param array $data
     * @param boolean $useValidationGroup
     * @return array
     */
    public function validate(array $data, $useValidationGroup = false)
    {
        $this->setData($data);
        
        if($useValidationGroup){
            $this->setValidationGroupByData($data);
        }
        
        $result = array(
            'valid' => $this->isValid(),
            'values' => $this->getValues(),
            'messages' => $this->getMessages()
        );
        
        return $result;
    }
    
    private function setValidationGroupByData(array $data)
    {
        $group = array_keys($data);
        
        foreach(array_keys($this->getSubInputFilters()) as $subKey){
            if(isset($data[$subKey]) && is_array($data[$subKey])){
                $group[$subKey] = array_keys($data[$subKey]);
        
                $key = array_search($subKey, $group);
                if($key !== false){
                    unset($group[$key]);
                }
            }
        }
        
        $this->setValidationGroup($group);
    }
    
    private function getSubInputFilters()
    {
        $inputFilters = array();
        
        foreach($this->inputs as $name => $input)
        {
            if($input instanceof InputFilterInterface){
                $inputFilters[$name] = $input;
            }    
        }
        
        return $inputFilters;
    }
}