<?php
namespace ValuTest\Model\TestAsset;

use Valu\Model\ArrayAdapter\ProviderInterface;

class MockModel implements ProviderInterface
{
    public static $arrayAdapter;
    
    public $id = '';
    
    public $name = '';
    
    public $meta = array();
    
    public $child = null;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getChild()
    {
        return $this->child;
    }
    
    public function setChild(MockModel $child)
    {
        $this->child = $child;
    }
    
    public function getMeta()
    {
        return $this->meta;
    }
    
    public function setMeta(array $meta)
    {
        $this->meta = $meta;
    }
    
    public function getArrayAdapter()
    {
        return static::$arrayAdapter;
    }

    public function toArray($extract) {
        return $this->getArrayAdapter()->toArray($this, $extract);
    }
}