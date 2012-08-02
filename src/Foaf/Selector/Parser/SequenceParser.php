<?php
namespace Foaf\Selector\Parser;

use Foaf\Selector;

class SequenceParser extends AbstractParser
{
    
    const PATH_ENCLOSURE = '/';
    
    /**
     * Parse simple selector sequence from pattern
     * 
     * @param string $pattern
     * @return \Foaf\Selector\Sequence
     */
    public function parse($pattern){
        
        $this->setPattern($pattern);
        
        $selectorParser = $this->getSimpleSelectorParser();
        $enclosures     = $selectorParser->getSelectorEnclosures();
        $selectorChars  = array();
        
        foreach ($enclosures as $enclosure){
            if($enclosure[0]){
                $selectorChars[$enclosure[0]] = 
                    isset($enclosure[1]) ? $enclosure[1] : null;
            }
        }
        
        // Start new sequence
        $sequence = new Selector\Sequence();
        
        // Parse simple selectors for current sequence
        do{
            // Set new cursor location
            $cursor = $this->seekKeyCursorLocation(true, $this->key());
            if($cursor === false) break;
        
            $this->cursor = $cursor;

            // Find the location of the next simple selector
            if($this->current() == self::PATH_ENCLOSURE){
                $simpleLast = false;
                $test = true;
            }
            else if(isset($selectorChars[$this->current()])){
                $simpleLast = $this->findChar(
                    $selectorChars[$this->current()],
                    $this->key()+1
                );
                
                if($simpleLast !== false){
                    $simpleLast++;
                }
            }
            else{
                $simpleLast = $this->findAny(
                    array_keys($selectorChars),
                    $this->key()+1
                );
            }
        
            if($simpleLast === false){
                $simpleLast = $this->length-1;
            }
            else{
                $simpleLast--;
            }
        
            $simpleLast = $this->seekKeyCursorLocation(false, $simpleLast);
        
            // Fetch the selector value
            $selectorValue = substr(
                $this->pattern,
                $this->key(),
                ($simpleLast-$this->key()+1)
            );

            $simpleSelector = $selectorParser->parse(
                $selectorValue
            );

            // Append to sequence
            $sequence->appendSimpleSelector($simpleSelector);
        
            // Proceed with next simple selector in sequence
            $this->cursor = $simpleLast + 1;
        }
        while($this->valid());
        
        return $sequence;
    }
    
    /**
     * Retrieve parser for simple selectors
     * 
     * @return SimpleSelectorParser
     */
    protected function getSimpleSelectorParser(){
        return new SimpleSelectorParser();
    }
}