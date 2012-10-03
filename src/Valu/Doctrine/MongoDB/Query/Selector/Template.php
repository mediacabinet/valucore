<?php
namespace Valu\Doctrine\MongoDB\Query\Selector;

use Valu\Selector\Selector as SelectorDefinition,
    Valu\Doctrine\MongoDB\Query\Selector,
    Doctrine\ODM\MongoDB\DocumentManager;

class Template
{
    protected $documentManager;
    
    protected $documentNames;
    
    protected $options;
    
    public function __construct(DocumentManager $documentManager, array $documentNames, $options = array()){
        $this->documentManager = $documentManager;
        $this->documentNames = $documentNames;
        $this->options = $options;
    }
    
    public function createSelector(SelectorDefinition $selector){
        return new Selector(
            $selector, 
            $this->documentManager, 
            $this->documentNames, 
            $this->options
        );
    }
}