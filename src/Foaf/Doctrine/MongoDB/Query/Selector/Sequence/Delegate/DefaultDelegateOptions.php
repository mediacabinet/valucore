<?php
namespace Foaf\Doctrine\MongoDB\Query\Selector\Sequence\Delegate;

use Zend\Stdlib\AbstractOptions;

class DefaultDelegateOptions extends AbstractOptions{
    
    protected $attributeMap = array(
        'foaf_role'     => 'roles',
        'foaf_class'    => 'classes',
        'foaf_path'     => 'path'
    );
    
    protected $roleAttribute = 'foaf_role';
    
    protected $classAttribute = 'foaf_class';
    
    protected $pathAttribute = 'foaf_path';
   
    /**
     * Retrieve attribute map
     * 
     * @return array
     */
    public function getAttributeMap()
    {
        return $this->attributeMap;
    }

    /**
     * Set attribute map
     * 
     * @param array $attributeMap
     */
	public function setAttributeMap(array $attributeMap)
    {
        $this->attributeMap = $attributeMap;
    }
    
    public function getRoleAttribute()
    {
        return $this->roleAttribute;
    }
    
    public function setRoleAttribute($roleAttribute)
    {
        $this->roleAttribute = $roleAttribute;
    }
    
    public function getClassAttribute()
    {
        return $this->classAttribute;
    }
    
    public function setClassAttribute($classAttribute)
    {
        $this->classAttribute = $classAttribute;
    }
    
    public function getPathAttribute()
    {
        return $this->pathAttribute;
    }
    
    public function setPathAttribute($pathAttribute)
    {
        $this->pathAttribute = $pathAttribute;
    }
}