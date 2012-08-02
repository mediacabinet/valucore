<?php
namespace Foaf\Doctrine\MongoDB\Query\Selector;

use Zend\Stdlib\AbstractOptions;
use Foaf\Doctrine\MongoDB\Query\Selector\Delegate;
use Foaf\Doctrine\MongoDB\Query\Selector\ReverseDelegate;

class SelectorOptions extends AbstractOptions{
    
    /**
     * Delegate object
     *
     * @var Delegate
     */
    protected $delegate;
    
    /**
     * Sequence options
     *
     * @var array\Traversable
     */
    protected $sequenceOptions;
    
    /**
     * Default element
     *
     * @var string
     */
    protected $defaultElement;
    
    /**
     * Retrieve default element
     *
     * @return string
     */
    public function getDefaultElement()
    {
        return $this->defaultElement;
    }
    
    /**
     * Set default element
     *
     * @param string $defaultElement
     */
    public function setDefaultElement($defaultElement)
    {
        $this->defaultElement = $defaultElement;
    }
    
    /**
     * Retrieve delegate instance
     *
     * @return Delegate
     */
    public function getDelegate(){
        if(!$this->delegate){
            $this->delegate = new ReverseDelegate();
        }
    
        return $this->delegate;
    }
    
    /**
     * Set delegate
     *
     * @param Delegate $delegate
     */
    public function setDelegate(Delegate $delegate){
        $this->delegate = $delegate;
    }
    
    /**
     * Retrieve sequence options
     * 
     * @return array|\Traversable
     */
	public function getSequenceOptions()
    {
        return $this->sequenceOptions;
    }

    /**
     * Set sequence options
     * 
     * @param array|\Traversable $sequenceOptions
     */
	public function setSequenceOptions($sequenceOptions)
    {
        $this->sequenceOptions = $sequenceOptions;
    }

    /**
     * Cast to array
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        $transform = function($letters) {
            $letter = array_shift($letters);
            return '_' . strtolower($letter);
        };
        foreach ($this as $key => $value) {
            $normalizedKey = preg_replace_callback('/([A-Z])/', $transform, $key);
            $array[$normalizedKey] = $value;
        }
        return $array;
    }
}