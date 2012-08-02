<?php
namespace Foaf\Service\Definition\Driver;

use Foaf\Service\Definition\DriverInterface;
use Foaf\Service\Definition;

class PhpDriver implements DriverInterface
{
    const COMMENT_NS = 'foaf\\service\\';
    
    protected static $classMeta = array();
    
    public function define($class){
        
        $reflectionClass = new \ReflectionClass($class);
        $comment = $reflectionClass->getDocComment();
        
        $definition = new Definition(array(
            'operations' => $this->resolveOperations($reflectionClass)        
        ));
        
        return $definition;
    }
    
    protected function resolveOperations(\ReflectionClass $class){
        
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $definition = array();
        
        foreach($methods as $method){
            $specs = $this->resolveMethod($method);
            
            if($specs !== false){
                $definition[$method->getName()] = $specs;
            }
        }
        
        return $definition;
    }
    
    protected function resolveMethod(\ReflectionMethod $method){
       
        $meta = $this->resolveMethodMeta($method);
        
        // Ignore if marked
        if(isset($meta['ignore'])){
            return false;
        }
        // Ignore all method names beginning with an underscore
        else if(substr($method->getName(), 0, 1) == '_'){
            return false;
        }
        else{
            
            // Ensure that declaring class is not ignored
            $classMeta = $this->resolveClassMeta($method->getDeclaringClass());
            
            if(isset($classMeta['ignore'])){
                return false;
            }
            
            $definition = array(
                'params' => $this->resolveMethodParameters($method),
                'meta' => $meta        
            );
            
            return $definition;
        }
    }
    
    protected function resolveMethodMeta(\ReflectionMethod $method){
        
        $comment = $method->getDocComment();
        return $this->parseDocBlock($comment, self::COMMENT_NS);
    }

    protected function resolveMethodParameters($method)
    {
        $params = array();
         
        foreach($method->getParameters() as $key => $param)
        {
            $params[$param->getName()] = array(
                'optional' => $param->isOptional(),
                'has_default_value' => $param->isDefaultValueAvailable(),
                'default_value' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            );
        }
         
        return $params;
    }
    
    protected function resolveClassMeta(\ReflectionClass $class)
    {
        if(!isset(self::$classMeta[$class->getName()])){
            self::$classMeta[$class->getName()] = $this->parseDocBlock(
                $class->getDocComment(), 
                self::COMMENT_NS
            );
        }
        
        return self::$classMeta[$class->getName()];
    }
    
    protected function parseDocBlock($comment, $ns, $exact = array()){
        
        $data = array();
        $lines = preg_split('/\r?\n\r?/', $comment);
        
        $lines = array_map(array($this, 'trimCommentLine'), $lines);
        
        if(sizeof($exact)){
            $match = $exact;
        }
        else{
            $match = array('*');
        }
        
        foreach($lines as $line){
            
            foreach($match as $expr){
                
                if($expr == '*'){
                    $expr = '[^ ]*';
                }
                else{
                    $expr = preg_quote($expr, '#');
                }
                
                $matches = null;
                
                if(preg_match('#^@'.preg_quote($ns).'('.$expr.')#i', $line, $matches)){
                    $key = strtolower($matches[1]);
                }
                else{
                    continue;
                }
                
                $value = trim(substr($line, strlen($matches[0])));
                
                if($value == ''){
                    $value = true;
                }
            
                $data[$key] = $value;
            }

        }
        
        return $data;
    }
    
    public function trimCommentLine($line){
        return ltrim(rtrim($line), "* \t\n\r\0\x0B");
    }
}