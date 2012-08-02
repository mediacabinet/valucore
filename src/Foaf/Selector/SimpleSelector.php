<?php
namespace Foaf\Selector;

interface SimpleSelector
{
    /**
     * Retrieve selector name
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Retrieve selector value
     *
     * @return string
     */
    public function getValue();
    
    /**
     * Retrieve selector pattern
     * 
     * @return string
     */
    public function getPattern();
    
    /**
     * Retrieve selector enclosure
     * 
     * @return array|string
     */
    public static function getEnclosure();
}