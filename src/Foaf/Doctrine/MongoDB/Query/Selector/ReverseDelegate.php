<?php
namespace Foaf\Doctrine\MongoDB\Query\Selector;

use Foaf\Selector\Selector as SelectorDefinition,
    Foaf\Selector\Sequence as SequenceDefinition,
    Foaf\Doctrine\MongoDB\Query\Selector,
    Doctrine\ODM\MongoDB\Query\Expr;

class ReverseDelegate implements Delegate
{

    /**
     * Selector
     * 
     * @var Selector
     */
    protected $selector = null;
    
    /**
     * Expression
     * 
     * @var Expr
     */
    protected $expression;
    
    /**
     * Combinator
     * 
     * @var string
     */
    protected $combinator;
    
    public function reversed(){
        return true;
    }
    
    public function combineSelector(Selector $currentSelector, Selector $newSelector, $combinator, Expr $expression)
    {
        $this->selector = $currentSelector;
        $this->expression = $expression;
        $this->combinator = $combinator;
        
        switch($combinator){
            case SelectorDefinition::COMBINATOR_CHILD:
                $this->combineParentSelector($newSelector);
                break;
            case SelectorDefinition::COMBINATOR_DESCENDENT:
                $this->combineAncestorSelector($newSelector);
                break;
            case SelectorDefinition::COMBINATOR_ANY_SIBLING:
                $this->combineSiblingSelector($newSelector);
                break;
            case SelectorDefinition::COMBINATOR_IMMEDIATE_SIBLING:
                $this->combineImmediateSiblingSelector($newSelector);
                break;
            default:
                $this->combineUnknownSelector($newSelector);
                break;
        }
    }
    
    protected function combineParentSelector(Selector $newSelector){
        throw new \Exception("Selector doesn't support child combinator");
    }
    
    protected function combineAncestorSelector(Selector $newSelector){
        throw new \Exception("Selector doesn't support descendent combinator");
    }
    
    protected function combineSiblingSelector(Selector $newSelector){
        throw new \Exception("Selector doesn't support sibling combinator");
    }
    
    protected function combineImmediateSiblingSelector(Selector $newSelector){
        throw new \Exception("Selector doesn't support immediate sibling combinator");
    }
    
    protected function combineUnknownSelector(Selector $newSelector){
        throw new \Exception(
            sprintf("Unknown combinator: %s", $this->combinator)
        );
    }
    
    protected function getSelector(){
        return $this->selector;
    }
    
    protected function getExpression(){
        return $this->expression;
    }
}