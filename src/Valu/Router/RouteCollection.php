<?php
namespace Valu\Router;

class RouteCollection 
    implements \Iterator
{

    private $position = 0;
    
    private $values = null;
    
    private $keys = null;
    
    public function __construct(RouteProviderInterface $routeProvider)
    {
        $this->routeProvider = $routeProvider;
    }
    
	public function current()
    {
        $this->load();
        
        if($this->valid()){
            $route = $this->values[$this->position];
            return $route;
        }
        else{
            return null;
        }
    }

	public function key()
    {
        return $this->keys[$this->position];
    }

	public function next()
    {
        ++$this->position;
    }

	public function rewind()
    {
        $this->position = 0;
    }

	public function valid()
    {
        $this->load();
        
        return isset($this->values[$this->position]);
    }

    protected function load(){
        if($this->values !== null){
            return;
        }
        
        $routes = $this->routeProvider->getRoutes();
        
        if(!is_array($routes)){
            $routes = array();
        }
        
        $this->keys = array_keys($routes);
        $this->values = array_values($routes);
    }
    
}