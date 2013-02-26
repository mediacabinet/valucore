<?php
namespace Valu\Doctrine\MongoDB\Query\Selector\Sequence\Delegate;

use Doctrine\ODM\MongoDB\Mapping\Types\Type;
use Valu\Doctrine\MongoDB\Query\Selector\Sequence\DelegateInterface;
use Valu\Selector\SimpleSelector\AbstractSelector;
use Valu\Doctrine\MongoDB\Query\Selector\Sequence;
use Valu\Selector\Selector;
use Valu\Selector\SimpleSelector;
use Valu\Selector\SimpleSelector\SimpleSelectorInterface;
use Valu\Doctrine\MongoDB\Query\Selector\Exception;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\DocumentManager;

class DefaultDelegate implements DelegateInterface
{
    
    /**
     * Options
     *
     * @var Zend\Stdlib\ParameterObject
     */
    protected $options;
    
    /**
     * Sequence
     * 
     * @var Sequence
     */
    protected $sequence;
    
    /**
     * Current document manager
     * 
     * @var DocumentManager
     */
    protected $documentManager;
    
    /**
     * Current element
     * 
     * @var string
     */
    protected $element;
    
    /**
     * Query builder
     *
     * @var Builder
     */
    protected $queryBuilder;
    
    /**
     * Expression
     * 
     * @var Expr
     */
    protected $expression;
    
    /**
     * Class name for options
     *
     * @var string
     */
    protected $optionsClass = 'Valu\Doctrine\MongoDB\Query\Selector\Sequence\Delegate\DefaultDelegateOptions';
    
    public function __construct($options = null){
        if($options != null){
            $this->setOptions($options);
        }
    }
    
    public function applySimpleSelector(Sequence $sequence, Builder $queryBuilder, Expr $expression, SimpleSelectorInterface $definition){
    
        $this->expression   = $expression;
        $this->queryBuilder = $queryBuilder;
        $this->sequence     = $sequence;
        $this->documentManager = $sequence->getDocumentManager(); 
        
        $name = $definition->getName();
        $success = false;
    
        switch($name){
            case AbstractSelector::SELECTOR_UNIVERSAL:
                $this->element = null;
                $success = true;
                break;
            case AbstractSelector::SELECTOR_ELEMENT:
                $this->element = $definition->getValue();
                $success = $this->applyElementSelector($definition);
                break;
            case AbstractSelector::SELECTOR_ID:
                $success = $this->applyIdSelector($definition);
                break;
            case AbstractSelector::SELECTOR_ROLE:
                $success = $this->applyRoleSelector($definition);
                break;
            case AbstractSelector::SELECTOR_CLASS:
                $success = $this->applyClassSelector($definition);
                break;
            case AbstractSelector::SELECTOR_PATH:
                $success = $this->applyPathSelector($definition);
                break;
            case AbstractSelector::SELECTOR_PSEUDO:
                $success = $this->applyPseudoSelector($definition);
                break;
            case AbstractSelector::SELECTOR_ATTRIBUTE:
                $success = $this->applyAttributeSelector($definition);
                break;
            default:
                $success = $this->applyUnknownSelector($definition);
                break;
        }
        
        return $success;
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
    public function getOption($key, $default = null)
    {
        return $this->getOptions()->__isset($key) ? $this->getOptions()->__get($key) : $default;
    }
    
    protected function applyElementSelector(SimpleSelector\Element $elementSelector){
        
        $class = $this->getElementMetadata($elementSelector->getValue());
        
        $discrField = $class->discriminatorField['name'];
        $discrValue = $class->discriminatorValue;
        
        if($discrField && $discrValue){
            $this->expression->field($discrField)->equals($discrValue);
            return true;
        } else {
            return false;
        }
    }
    
    protected function applyIdSelector(SimpleSelector\Id $idSelector){
        
        $element   = $this->requireElement();
        $class     = $this->getElementMetadata($element);
        $field     = $class->getIdentifier();
        
        if($field){
    
            if($class->isIdGeneratorAuto()){
                $condition = new \MongoId($idSelector->getCondition());
            }
            else{
                //TODO: might it be that there are user-generated MongoIds as well?
                $condition = $idSelector->getCondition();
            }
    
            $selector = new SimpleSelector\Attribute(
                $field,
                SimpleSelector\Attribute::OPERATOR_EQUALS,
                $condition
            );
    
            $this->applyAttributeSelector($selector);
            
            return true;
        }
        return false;
    }
    
    protected function applyRoleSelector(SimpleSelector\Role $roleSelector){
        
        $selector = new SimpleSelector\Attribute(
            $this->getOption('role_attribute'),
            SimpleSelector\Attribute::OPERATOR_IN_LIST,
            $roleSelector->getCondition()
        );
        
        return $this->applyAttributeSelector($selector);
    }
    
    protected function applyClassSelector(SimpleSelector\ClassName $classSelector){
        $selector = new SimpleSelector\Attribute(
            $this->getOption('class_attribute'),
            SimpleSelector\Attribute::OPERATOR_IN_LIST,
            $classSelector->getCondition()
        );

        return $this->applyAttributeSelector($selector);
    }
    
    protected function applyPathSelector(SimpleSelector\Path $pathSelector){
        
        if (sizeof($pathSelector->getPathItems())) {
            $path = $this->translatePathArray($pathSelector->getPathItems());
            
            // Apply expression that causes this query to return null
            if (!$path) {
                $selector = new SimpleSelector\Attribute(
                        '_id',
                        SimpleSelector\Attribute::OPERATOR_EQUALS,
                        false
                );
            
                return $this->applyAttributeSelector($selector);
            }
            
            $condition =    '^' .
                    SimpleSelector\Path::PATH_SEPARATOR .
                    implode(SimpleSelector\Path::PATH_SEPARATOR, $path) .
                    '$';
            
            $selector = new SimpleSelector\Attribute(
                    $this->getOption('path_attribute'),
                    SimpleSelector\Attribute::OPERATOR_REG_EXP,
                    $condition
            );
            
        } else {
            // Query root
            $selector = new SimpleSelector\Attribute(
                $this->getOption('path_attribute'),
                SimpleSelector\Attribute::OPERATOR_EQUALS,
                SimpleSelector\Path::PATH_SEPARATOR
            );
        }
    
        return $this->applyAttributeSelector($selector);
    }
    
    /**
     * Template method for path translation
     * 
     * @param array $items Path items
     * @return array
     * @throws \Exception
     */
    protected function translatePathArray(array $items){
        
        if(!sizeof($items)){
            return array();
        } elseif($items[0] instanceof Selector) {
            
           $documentName = $this->getOption('path_document', $this->getDocument());
            
           // Create a new Query Builder instance
           $qb = $this->getDocumentManager()->createQueryBuilder(
               $documentName);
           
           $attr = $this->mapAttribute($this->getOption('path_attribute'));
           
           // Select path attribute
           $qb ->select($attr)
               ->hydrate(false);
           
           // Use template to create a new selector query
           $template = $this->getSequence()->getSelectorTemplate();
           $selector = $template->createSelector($items[0]);
           $selector->setDocumentNames(array(Sequence::DEFAULT_ELEMENT => $documentName));
           $selector->extendQuery($qb);
           
           // Fetch data
           $result = $qb->limit(1)
               ->getQuery()
               ->getSingleResult();
           
           // Exit early if selector could not be resolved
           if (!$result || !isset($result[$attr]) || !$result[$attr]) {
               return false;
           }
           
           $path = ltrim($result[$attr], '/');
           
           unset($items[0]);
           $items = array_map('stripslashes', $items);
           
           $items = array_merge(
               explode(SimpleSelector\Path::PATH_SEPARATOR, $path),
               array_values($items)
           );
        } else {
            $items = array_map('stripslashes', $items);
        }
        
        // Convert string items to reg exp
        foreach($items as &$value){
        
            if(is_string($value)){
                $value = preg_quote($value, '/');
                $value = str_replace('\*', '.*', $value);
            }
        
        }
        
        return $items;
    }
    
    protected function applyAttributeSelector(SimpleSelector\Attribute $attrSelector){
    
        $operator = $attrSelector->getOperator();
        $attr     = $attrSelector->getAttribute();
        $cond     = $attrSelector->getCondition();
        $element  = $this->getElement();
        
        /**
         * Map attribute name
         */
        $attr = $this->mapAttribute($attr);
        
        // Field expression
        $field = $this->expression->field($attr);
        
        // Convert based on field type
        $meta = $this->getElementMetadata($element);
        
        $fields = explode('.', $attr);
        foreach ($fields as $index => $fieldName) {
            if ($meta->hasAssociation($fieldName)) {
                $fieldMapping = $meta->getFieldMapping($fieldName);
                $meta =  $this->getDocumentManager()->getClassMetadata($fieldMapping['targetDocument']);
            } elseif($index === (sizeof($fields)-1)) {
                $type = $meta->getTypeOfField($attr);
                
                if (!$type) {
                    foreach ($meta->parentClasses as $class)
                    {
                        $type = $this->getDocumentManager()->getClassMetadata($class)->getTypeOfField($attr);
                        if($type) break;
                    }
                }
                
                if ($type && $type !== 'collection' && $type !== 'one') {
                    $cond = Type::getType($type)->convertToDatabaseValue($cond);
                }
            } else {
                throw new Exception\UnknownFieldException(
                    sprintf("Unknown field '%s'", $attr));
            }
        }
        
        switch ($operator) {
            case null:
                $field->exists(true);
                break;
            case SimpleSelector\Attribute::OPERATOR_EQUALS:
                $field->equals($cond);
                break;
            case SimpleSelector\Attribute::OPERATOR_NOT_EQUALS:
                $field->notEqual($cond);
                break;
            case SimpleSelector\Attribute::OPERATOR_GREATER_THAN:
                $field->gt($cond);
                break;
            case SimpleSelector\Attribute::OPERATOR_GREATER_THAN_OR_EQUAL:
                $field->gte($cond);
                break;
            case SimpleSelector\Attribute::OPERATOR_LESS_THAN:
                $field->lt($cond);
                break;
            case SimpleSelector\Attribute::OPERATOR_LESS_THAN_OR_EQUAL:
                $field->lte($cond);
                break;
            case SimpleSelector\Attribute::OPERATOR_IN_LIST:
    
                $list = explode(' ', $cond);
                array_map('trim', $list);
                
                $field->in($list);
                break;
            case SimpleSelector\Attribute::OPERATOR_REG_EXP:
                $re = new \MongoRegex('/'.$cond.'/');
                $field->equals($re);
                break;
            case SimpleSelector\Attribute::OPERATOR_SUBSTR_MATCH:
                $re = new \MongoRegex('/.*'.preg_quote($cond, '/').'.*/');
                $field->equals($re);
                break;
            case SimpleSelector\Attribute::OPERATOR_SUBSTR_PREFIX:
                $re = new \MongoRegex('/^'.preg_quote($cond, '/').'.*/');
                $field->equals($re);
                break;
            case SimpleSelector\Attribute::OPERATOR_SUBSTR_SUFFIX:
                $re = new \MongoRegex('/'.preg_quote($cond, '/').'$/');
                $field->equals($re);
                break;
            default:
                throw new Exception\UnknownOperatorException(
                    sprintf("Unknown operator '%s'", $operator));
                break;
        }
        
        return true;
    }
    
    protected function applyPseudoSelector(SimpleSelector\Pseudo $pseudoSelector){
        
        if($pseudoSelector instanceof SimpleSelector\Pseudo\Sort){
            
            $this->getQueryBuilder()->sort(
                $pseudoSelector->getAttribute(), 
                $pseudoSelector->getOrder()
            );
            
            return true;
        } elseif ($pseudoSelector->getClassName() == 'limit') {
            $this->getQueryBuilder()->limit(intval($pseudoSelector->getClassValue()));
            return false;
        } elseif (in_array($pseudoSelector->getClassName(), ['startingFrom', 'offset'])) {
            $this->getQueryBuilder()->skip(intval($pseudoSelector->getClassValue()));
            return false;
        } else{
            return false;
        }
    }
    
    protected function applyUnknownSelector(SelectorInterface $simpleSelector){
        throw new Exception\UnknownSimpleSelectorException(
            sprintf('Unknown selector "%s"', (string) $simpleSelector));
    }
    
    /**
     * Retrieve current sequence
     * 
     * @return Sequence
     */
    protected function getSequence(){
        return $this->sequence;
    }
    
    /**
     * Retrieve query builder
     *
     * @return Builder
     */
    protected function getQueryBuilder(){
        return $this->queryBuilder;
    }
    
    /**
     * Retrieve current expression
     * 
     * @return Expr
     */
    protected function getExpression(){
        return $this->expression;
    }
    
    /**
     * Retrieve current document manager instance
     * 
     * @return DocumentManager
     */
    protected function getDocumentManager(){
        return $this->documentManager;
    }
    
    /**
     * Retrieve current document names in an associative
     * array where each key is a name of an element and 
     * value corresponding class name
     * 
     * @return array
     */
    protected function getDocumentNames(){
        return $this->getSequence()->getDocumentNames();
    }
    
    /**
     * Retrieve meta data for class represented by element
     * 
     * @param string $element
     * @return \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    protected function getElementMetadata($element){
        $names = $this->getDocumentNames();
        
        if (!isset($names[$element])) {
            throw new \Exception(
                sprintf('Unknown document %s', $element));
        }
        
        $className = $names[$element];
        return $this->getDocumentManager()->getClassMetadata($className);
    }
    
    /**
     * Retrieve current element name
     * 
     * @return array
     */
    protected function getElement(){
        return $this->element;
    }
    
    /**
     * Retrieve current document name
     * 
     * @return string
     */
    protected function getDocument(){
        if($this->getElement()){
            $map = $this->getDocumentNames();
            return $map[$this->getElement()];
        }
        else{
            return null;
        }
    }
    
    /**
     * Retrieve current element name and throw
     * exception if not found
     * 
     * @throws \Exception
     * @return string
     */
    protected function requireElement(){
        $element = $this->getElement();
        
        if(!$element){
            throw new \Exception("Sequence doesn't contain element information");
        }
        
        return $element;
    }
    
    /**
     * Map attribute name to corresponding
     * field name in MongoDB
     * 
     * @param string $attr
     * @return string
     */
    protected function mapAttribute($attr){
        $map = $this->getOption('attribute_map');
        
        /**
         * Map attribute name
         */

        if(array_key_exists($attr, $map)){
            $attr = $map[$attr];
        }
        
        return $attr;
    }
}