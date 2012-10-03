<?php
namespace Valu\Doctrine\MongoDB\Query\Selector;

use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\DocumentManager;
use Valu\Selector\Sequence as SequenceDefinition;
use Valu\Selector\SimpleSelector;
use Valu\Selector\SimpleSelector\SimpleSelectorInterface;
use Valu\Doctrine\MongoDB\Query\Selector\Template;
use Valu\Doctrine\MongoDB\Query\Selector\Sequence\Delegate as SequenceDelegate;

/**
 * CSS selector based query
 * 
 * @author juhasuni
 *
 */
class Sequence
{
    
    /**
     * Sequence definition
     * 
     * @var Valu\Selector\Sequence
     */
    protected $sequence;
    
    /**
     * Document manager
     *
     * @var DocumentManager
     */
    protected $documentManager;
    
    /**
     * Document class names
     *
     * @var array
     */
    protected $documentNames;
    
    /**
     * Query builder
     *
     * @var Doctrine\ODM\MongoDB\Query\Builder
     */
    protected $queryBuilder;
    
    /**
     * Delegate object
     *
     * @var Delegate
     */
    protected $delegate;
    
    /**
     * Class name for options
     *
     * @var string
     */
    protected $optionsClass = 'Valu\Doctrine\MongoDB\Query\Selector\Sequence\SequenceOptions';
    
    /**
     * Selector template
     * 
     * @var Template
     */
    protected $selectorTemplate;
    
    /**
     * Options
     *
     * @var Zend\Stdlib\ParameterObject
     */
    protected $options;
    
    public function __construct(SequenceDefinition $sequence, DocumentManager $documentManager, 
            array $documentNames, Template $selectorTemplate, $options = null){
        
        $this->sequence = $sequence;
        $this->documentManager = $documentManager;
        $this->documentNames = $documentNames;
        $this->selectorTemplate = $selectorTemplate;
        
        if(!is_null($options)){
            $this->setOptions($options);
        }
    }
    
    /**
     * Extend query with selector sequence
     *
     * @param $queryBuilder Builder
     * @param $expr Expr
     */    
    public function extendQuery(Builder $queryBuilder, Expr $expression = null){
        $this->queryBuilder = $queryBuilder;
        
        if($expression === null){
            $expression = $queryBuilder->expr();
            $newExpression = true;
        }
        else{
            $newExpression = false;
        }
        
        // Retrieve all items
        $items = $this->sequence->getItems();
        
        /**
         * Determine all element names for universal selectors
         */
        if($this->sequence->isUniversal()){
            $elements = array_keys($this->getDocumentNames());
            
            // Remove universal selector
            array_shift($items);
        }
        /**
         * Use the current element name
         */
        else if(($element = $this->sequence->getElement()) !== null){
            $elements[] = $element;
            
            // Remove element selector
            array_shift($items);
        }
        /**
         * If only one document name is defined, use that
         * as the default
         */
        else if(sizeof($this->getDocumentNames()) == 1){
            $elements = array_keys($this->getDocumentNames());
        }
        /**
         * Use the defined default element name
         */
        else if(($element = $this->getOption('default_element')) !== null){
            $elements[] = $element;
        }
        /**
         * Fallback: consider universal
         */
        else{
            $elements = array_keys($this->getDocumentNames());
        }
        
        array_unshift(
            $items, 
            null
        );
        
        /**
         * New expression if multiple elements are queried
         */
        if(sizeof($elements) > 1){
            $andExpr = $queryBuilder->expr();
        }
        else{
            $andExpr = null;
        }
        
        /**
         * Apply same sequence for each element
         */
        foreach($elements as $element){
            
            // Prepend element selector
            $elementSelector = new SimpleSelector\Element($element);
            $items[0] = $elementSelector;
            
            if($andExpr !== null){
                $expr = $queryBuilder->expr();
            }
            else{
                $expr = $expression;
            }
            
            /**
             * Apply each simple selector in sequence using
             * delegate
             */
            foreach ($items as $item){
                if($item instanceof SimpleSelectorInterface){
                    $this->getDelegate()->applySimpleSelector($this, $queryBuilder, $expr, $item);
                }
                else{
                    throw new \Exception(
                        sprintf(
                            'Unrecognized item in sequence "%s" at position %d',
                            (string) $this->sequence,
                            $this->sequence->key()
                        )
                    );
                }
            }
            
            /**
             * Join each element's sequence with logical OR
             */
            if($andExpr !== null){
                $andExpr->addOr($expr);
            }
        }
        
        /**
         * Join element specific expressions with AND
         */
        if($andExpr !== null){
            $expression->addAnd($andExpr);
        }
        
        /**
         * Apply to query builder
         */
        if($newExpression){
            $queryBuilder->addAnd($expression);
        }
    }
    
    /**
     * Retrieve selector template
     *
     * @return \Valu\Doctrine\MongoDB\Query\Selector\Template
     */
    public function getSelectorTemplate(){
        if(!$this->selectorTemplate){
            $this->selectorTemplate = new Template(
                    $this->getDocumentManager(),
                    $this->getDocumentNames(),
                    $this->getOptions()
            );
        }
    
        return $this->selectorTemplate;
    }
    
    /**
     * Retrieve delegate instance
     *
     * @return Delegate
     */
    public function getDelegate(){
        return $this->getOption('delegate');
    }
    
    /**
     * Set delegate
     *
     * @param Delegate $delegate
     */
    public function setDelegate(SequenceDelegate $delegate){
        $this->setOption('delegate', $delegate);
    }
    
    /**
     * Retrieve document manager instance
     *
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    public function getDocumentManager(){
        return $this->documentManager;
    }
    
    /**
     * Retrieve document class names
     *
     * @return string
     */
    public function getDocumentNames(){
        return $this->documentNames;
    }
    
    /**
     * Set service options
     *
     * @param  array|Traversable $options
     * @return Service
     */
	public function setOptions($options)
    { 
    	$this->options = new $this->optionsClass($options);
        return $this;
    }
    
	/**
     * Retrieve service options
     *
     * @return array
     */
    public function getOptions()
    {
        if(!$this->options){
            $this->options = new $this->optionsClass(array());
        }
        
        return $this->options;
    }

    /**
     * Is an option present?
     *
     * @param  string $key
     * @return bool
     */
    public function hasOption($key)
    {
        return $this->getOptions()->__isset($key);
    }
    
    /**
     * Set option
     * 
     * @param string $key
     * @param mixed $value
     * @return Service
     */
    public function setOption($key, $value)
    {
    	$this->getOptions()->__set($key, $value);
    	return $this;
    }

    /**
     * Retrieve a single option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->getOptions()->__get($key);
    }
}