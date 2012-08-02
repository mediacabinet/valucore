<?php
namespace Foaf\Selector\SimpleSelector;

use Foaf\Selector\SimpleSelector\Attribute,
    Foaf\Selector\SimpleSelector\AbstractSelector;

class Role extends Attribute
{
    protected $name = AbstractSelector::SELECTOR_ROLE;
    
    public function __construct($value)
    {
        parent::__construct('role', Attribute::OPERATOR_EQUALS, $value);
    }
    
    public function getPattern(){
        return array_pop(self::getEnclosure()) . $this->getCondition();
    }
    
    public static function getEnclosure()
    {
        return array('$');
    }
}