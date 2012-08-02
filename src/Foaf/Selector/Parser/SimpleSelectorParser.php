<?php
namespace Foaf\Selector\Parser;

use Foaf\Selector\SimpleSelector;

class SimpleSelectorParser extends AbstractParser
{
    /**
     * Selector enclosures
     * @var array
     */
    protected $enclosures = null;
    
    /**
     * Array of selector classes
     * @var array
     */
    protected $selectors = array(
        'Foaf\Selector\SimpleSelector\Universal',        
        'Foaf\Selector\SimpleSelector\Element',        
        'Foaf\Selector\SimpleSelector\Id',        
        'Foaf\Selector\SimpleSelector\Role',        
        'Foaf\Selector\SimpleSelector\ClassName',        
        'Foaf\Selector\SimpleSelector\Path',        
        'Foaf\Selector\SimpleSelector\Attribute',        
        'Foaf\Selector\SimpleSelector\Pseudo',        
    );
    
    /**
     * Array of parsers for selector class
     * @var array
     */
    protected $parsers = array(
        'Foaf\Selector\SimpleSelector\Attribute'
            => 'Foaf\Selector\Parser\AttributeSelectorParser',
        'Foaf\Selector\SimpleSelector\Path'
            => 'Foaf\Selector\Parser\PathSelectorParser',
        'Foaf\Selector\SimpleSelector\Pseudo'
            => 'Foaf\Selector\Parser\PseudoParser'
    );
    
    /**
     * Parse simple selector pattern to fetch corresponding
     * simple selector instance
     * 
     * @param string $pattern
     * @return SimpleSelector|null
     */
    public function parse($pattern){
        $this->setPattern($pattern);
        
        $selector    = null;
        $enclosures  = $this->getSelectorEnclosures();
        $match       = null;
        $value       = null;
        
        foreach ($enclosures as $class => $enclosure){
            
            /**
             * Match any word character for empty enclosure
             */
            if($enclosure[0] == '' && preg_match('/\w/', $this->current())){
                $match = $class;
                $value = $pattern;
                break;
            }
            /**
             * Match first character against enclosure
             */
            else if(sizeof($enclosure) == 1 && $this->current() == $enclosure[0]){
                $match = $class;
                $value = substr($this->pattern, 1);
                break;
            }
            /**
             * Match first and last character against enclosure
             */
            else if(sizeof($enclosure) > 1){
                $endPosition = $this->findChar($enclosure[1], $this->length-1);
                
                if($this->current() == $enclosure[0] && $endPosition !== false){
                    $match = $class;
                    $value = substr($this->pattern, 1, -1);
                    break;
                }
            }
        }
        
        if($match){
            
            /**
             * Use selector parser if defined
             */
            if(isset($this->parsers[$match])){
                $parser = new $this->parsers[$match]();
                $selector = $parser->parse($value);
            }
            /**
             * No parsing required, create a new selector
             * instance
             */
            else{
                $class    = '\\' . $match;
                $selector = new $class($value);
            }
        }
        
        return $selector;
    }
    
    public function getSelectorEnclosures()
    {
        if(is_null($this->enclosures)){
            $this->enclosures = array();
            
            foreach($this->selectors as $class){
                $enclosure = call_user_func('\\'.$class.'::getEnclosure');
                if(!is_array($enclosure)) $enclosure = array($enclosure);
                
                $this->enclosures[$class] = $enclosure;
            }
        }
        
        return $this->enclosures;
    }
}