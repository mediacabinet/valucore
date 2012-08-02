<?php
namespace Foaf\Selector\SimpleSelector;

use Foaf\Selector\SimpleSelector\AbstractSelector;

class Element extends AbstractSelector
{
    protected $name = AbstractSelector::SELECTOR_ELEMENT;
    
    public static function getEnclosure()
    {
        return array('');
    }
}