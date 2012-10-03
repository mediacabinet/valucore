<?php
namespace Valu\Doctrine\MongoDB\Query\Selector;

use Valu\Selector\Sequence as SequenceDefinition,
    Doctrine\ODM\MongoDB\Query\Expr,
    Valu\Doctrine\MongoDB\Query\Selector;

interface Delegate
{
    
    /**
     * Test whether delegate supports reversed
     * sequence processing order
     * 
     * @return boolean
     */
    public function reversed();

    /**
     * Combine 
     * @param Selector $currentSelector
     * @param Selector $newSelector
     * @param string $combinator
     * @param Expr $expression
     */
    public function combineSelector(Selector $currentSelector, Selector $newSelector, $combinator, Expr $expression);
}