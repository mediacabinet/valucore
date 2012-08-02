<?php
namespace Foaf\Doctrine\MongoDB\Query\Selector\Sequence;

use Zend\Stdlib\AbstractOptions;
use Foaf\Doctrine\MongoDB\Query\Selector\Sequence\Delegate;
use Foaf\Doctrine\MongoDB\Query\Selector\Sequence\Delegate\DefaultDelegate;

class SequenceOptions extends AbstractOptions{
    
    /**
     * Sequence delegate
     * 
     * @var Delegate
     */
    protected $delegate;
    
    /**
     * Sequence delegate options
     *
     * @var array|Traversable
     */
    protected $delegateOptions;
    
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
            $this->delegate = new DefaultDelegate(
                $this->getDelegateOptions()        
            );
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
     * Retrieve options for default sequence delegate
     *
     * @return array|\Traversable $sequenceDelegateOptions
     */
    public function getDelegateOptions()
    {
        return $this->delegateOptions;
    }
    
    /**
     * Set options for default sequence delegate
     *
     * @param array|\Traversable $sequenceDelegateOptions
     */
    public function setDelegateOptions($sequenceDelegateOptions)
    {
        $this->delegateOptions = $sequenceDelegateOptions;
    }
}