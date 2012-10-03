<?php
namespace Valu\Selector\SimpleSelector;

use Valu\Selector\SimpleSelector\AbstractSelector;

class Universal extends AbstractSelector
{
    protected $name = AbstractSelector::SELECTOR_UNIVERSAL;
    
    public function __construct(){
        parent::__construct('');
    }
    
    public static function getEnclosure()
    {
        return array('*');
    }
}