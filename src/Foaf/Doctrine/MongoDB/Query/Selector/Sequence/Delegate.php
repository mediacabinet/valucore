<?php
namespace Foaf\Doctrine\MongoDB\Query\Selector\Sequence;

use Foaf\Doctrine\MongoDB\Query\Selector\Sequence,
    Foaf\Selector\SimpleSelector as SimpleSelectorDefinition,
    Doctrine\ODM\MongoDB\Query\Builder,
    Doctrine\ODM\MongoDB\Query\Expr;

interface Delegate{
    public function applySimpleSelector(Sequence $sequence, Builder $queryBuilder, Expr $expression, SimpleSelectorDefinition $definition);
}