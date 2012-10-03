<?php
namespace Valu\Service;

class Definition
{
    
    /**
     * Definition specs
     * 
     * @var array
     */
    protected $definition;
    
    public function __construct($definition){
        
        if(!is_array($definition) && !($definition instanceof \Traversable)){
            throw new \InvalidArgumentException('Invalid argument $definition, array or instance of Traversable expected');
        }
        
        if($definition instanceof \Traversable){
            $definition = \Zend\Stdlib\ArrayUtils::iteratorToArray($definition);
        }
        
        $this->definition = $definition;
    }
    
    public function getVersion(){
        return $this->definition['version'];
    }
    
    public function setVersion($version)
    {
        $this->definition['version'] = $version;   
    }
    
    /**
     * Does operation exist?
     * 
     * @param string $operation
     * @return boolean
     */
    public function hasOperation($operation)
    {
        return isset($this->definition['operations'][$operation]);
    }
    
    /**
     * Define operation
     * 
     * @param string $operation Name of the operation
     * @return array|boolean Operation definition or false if operation is not defined
     */
    public function defineOperation($operation){
        if($this->hasOperation($operation)){
            return $this->definition['operations'][$operation];
        }
        else{
            return false;
        }
    }
}