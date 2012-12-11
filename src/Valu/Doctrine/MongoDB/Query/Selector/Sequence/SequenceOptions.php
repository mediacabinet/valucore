<?php
namespace Valu\Doctrine\MongoDB\Query\Selector\Sequence;

use Zend\Stdlib\AbstractOptions;
use Valu\Doctrine\MongoDB\Query\Selector\Sequence\DelegateInterface;
use Valu\Doctrine\MongoDB\Query\Selector\Sequence\Delegate\DefaultDelegate;

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
    public function setDelegate(DelegateInterface $delegate){
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