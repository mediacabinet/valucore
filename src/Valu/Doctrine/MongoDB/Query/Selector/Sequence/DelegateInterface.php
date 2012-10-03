<?php
namespace Valu\Doctrine\MongoDB\Query\Selector\Sequence;

use Valu\Doctrine\MongoDB\Query\Selector\Sequence;
use Valu\Selector\SimpleSelector\SimpleSelectorInterface;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;

interface DelegateInterface{
    public function applySimpleSelector(Sequence $sequence, Builder $queryBuilder, Expr $expression, SimpleSelectorInterface $definition);
}