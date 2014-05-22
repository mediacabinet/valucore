<?php
namespace Valu\InputFilter;

use Zend\InputFilter\Input;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\InputFilter\InputFilter as ZendInputFilter;
use Zend\InputFilter\InputFilterInterface;

class InputFilter 
    extends ZendInputFilter
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
        } else {
            $this->setValidationGroup(ZendInputFilter::VALIDATE_ALL);
        }
        
        if($validate && !$this->isValid()){
            
            $messages = $this->getMessages();
            $fields   = array();
            while(is_array($messages)){
                $keys     = array_keys($messages);
                $key      = array_shift($keys);
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
        } else {
            $this->setValidationGroup(ZendInputFilter::VALIDATE_ALL);
        }
        
        $result = array(
            'valid' => $this->isValid(),
            'values' => $this->getValues(),
            'messages' => $this->getMessages()
        );
        
        return $result;
    }

    /**
     * Inject service locator to all inputs and their filter and validator
     * chain plugin managers
     * 
     * Use this method to inject service locator so that it becomes available
     * for filters and validators. This is usefuly especially for validators or
     * filters that are serialized and cannot contain reference to service locator.
     * 
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::setServiceLocator()
     */
    public function setMainServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        foreach ($this->inputs as $input) {
            
            if ($input instanceof ServiceLocatorAwareInterface) {
                $input->setServiceLocator($serviceLocator);
            } 
            
            // Inject ServiceLocator to filter and validator chains
            if($input instanceof Input) {
                $input->getFilterChain()
                    ->getPluginManager()
                    ->setServiceLocator($serviceLocator);
                
                $input->getValidatorChain()
                    ->getPluginManager()
                    ->setServiceLocator($serviceLocator);
            }
        }
    }
    
    public function __sleep()
    {
        // Serialize only inputs
        return array('inputs');
    }
    
    /**
     * Set validation group by filter data, using data keys
     * as validation group properties
     * 
     * @param array $data
     */
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
    
    /**
     * Fetch child input filters
     * 
     * @return multitype:\Zend\InputFilter\InputFilterInterface
     */
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