<?php
namespace Foaf\Selector\Parser;

use Foaf\Selector\SimpleSelector,
    Foaf\Selector\Parser\AbstractParser;

class PathSelectorParser extends AbstractParser
{
    /**
     * Parse pseudo selector from pattern
     *
     * @param string $pattern
     */
    public function parse($pattern){
        
        $this->setPattern($pattern);
        
        if($this->pattern == ''){
            $items = array();
        }
        else{
            $items = $this->parsePattern();
        }

        $selector = new SimpleSelector\Path(
            $items      
        );
        
        return $selector;
    }
    
    protected function parsePattern(){
        
        // Split by path enclosure
        $enclosure = SimpleSelector\Path::getEnclosure();
        $enclosure = array_pop($enclosure);
        $items     = explode($enclosure, $this->pattern);
        
        // Valid selectors for first item
        $enclosures = array_merge(
                SimpleSelector\Id::getEnclosure(),
                SimpleSelector\Role::getEnclosure()
        );
        
        // Parse first item as a child selector
        if(in_array($this->current(), $enclosures)){
            $parser = new SelectorParser();
            $items[0] = $parser->parse($items[0]);
        }
        
        // Convert string items to reg exp
        foreach($items as &$value){
        
            if(is_string($value)){
                $value = preg_quote($value, '/');
                $value = str_replace('\*', '[^/]*', $value);
            }
        
        }
        
        return $items;
    }
}