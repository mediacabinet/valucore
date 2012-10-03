<?php
namespace Valu\Doctrine\MongoDB\Query\Selector\Sequence\Delegate;

use Zend\Stdlib\AbstractOptions;

class DefaultDelegateOptions extends AbstractOptions{
    
    protected $attributeMap = array(
        'valu_role'     => 'roles',
        'valu_class'    => 'classes',
        'valu_path'     => 'path'
    );
    
    protected $roleAttribute = 'valu_role';
    
    protected $classAttribute = 'valu_class';
    
    protected $pathAttribute = 'valu_path';
    
    protected $pathDocument = null;
   
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
    
	/**
     * @return the $pathDocument
     */
    public function getPathDocument()
    {
        return $this->pathDocument;
    }

	/**
     * @param NULL $pathDocument
     */
    public function setPathDocument($pathDocument)
    {
        $this->pathDocument = $pathDocument;
    }

    
}