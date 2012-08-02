<?php
namespace Foaf\Selector\SimpleSelector;

use Foaf\Selector\SimpleSelector\Attribute,
    Foaf\Selector\SimpleSelector\AbstractSelector;

class Id extends Attribute
{
    protected $name = AbstractSelector::SELECTOR_ID;
    
    public function __construct($value)
    {
        parent::__construct('id', Attribute::OPERATOR_EQUALS, $value);
    }
    
    public function getPattern(){
        return array_pop(self::getEnclosure()) . $this->getCondition();
    }
    
    public static function getEnclosure()
    {
        return array('#');
    }
}