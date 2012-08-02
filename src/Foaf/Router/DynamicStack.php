<?php
namespace Foaf\Router;

use Zend\Stdlib\RequestDescription as Request,
	Zend\Mvc\Router\SimpleRouteStack,
	Zend\Mvc\Router\Http\RouteMatch,
	Zend\Mvc\Router\Http\RouteInterface,
    Foaf\Router\Route\Provider;

class DynamicStack extends SimpleRouteStack implements RouteInterface{
	
	/**
	 * Route providers
	 * 
	 * @var array
	 */
	protected $routeProviders = array();
	
	/**
	 * List of assembled parameters.
	 *
	 * @var array
	 */
	protected $assembledParams = array();
	
	/**
	 * match(): defined by Route interface.
	 *
	 * @see    Route::match()
	 * @param  Request $request
	 * @return RouteMatch
	 */
	public function match(Request $request, $pathOffset = null)
	{
		$providers = $this->getProviders();
		
		if(sizeof($providers)){
			foreach ($providers as $provider){
				$routeMatch = $provider->match(
					$request, 
					$pathOffset
				);
				
				if($routeMatch instanceof RouteMatch){
					return $routeMatch;
				}
			}
		}
		
		return null;
	}
	
	/**
	 * assemble(): defined by Route interface.
	 *
	 * @see    Route::assemble()
	 * @param  array $params
	 * @param  array $options
	 * @return mixed
	 */
	public function assemble(array $params = array(), array $options = array())
	{
		if (!isset($options['name'])) {
			throw new \InvalidArgumentException('Missing "name" option');
		}
	
		$route = $this->getNamedRoute($options['name']);
	
		if (!$route) {
			throw new \RuntimeException(sprintf('Route with name "%s" not found', $options['name']));
		}
	
		unset($options['name']);
	
		$this->assembledParams = array();
		$path = $route->assemble($params, $options);
		$this->assembledParams = $route->getAssembledParams();
		
		return $path;
	}
	
	/**
	 * getAssembledParams(): defined by Route interface.
	 *
	 * @see    Route::getAssembledParams
	 * @return array
	 */
	public function getAssembledParams(){
		return $this->assembledParams;
	}

	public function hasProvider($name){
		return array_key_exists($name, $this->routeProviders);
	}
	
	public function addProvider($name, Provider $container){
		
		if(!is_string($name)){
			throw new \InvalidArgumentException('Parameter $name must be a string');
		}
		
		if($this->hasProvider($name)){
			throw new \Exception('Router provider by name '.$name.' already exists');
		}
		
		$this->routeProviders[$name] = $container;
		
		return $this;
	}
	
	public function addProviders(array $providers){
		if(sizeof($providers)){
			foreach ($providers as $name => $provider){
				$this->addProvider($name, $provider);
			}
		}
		
		return $this;
	}
	
	public function setProviders(array $providers){
		$this->clearProviders();
		$this->addProviders($providers);
		
		return $this;
	}
	
	public function removeProvider($name){
		if(array_key_exists($name, $this->routeProviders)){
			unset($this->routeProviders[$name]);
		}
		
		return $this;
	}
	
	public function clearProviders(){
		$this->routeProviders = array();
	}
	
	public function getProvider($name){
		if($this->hasProvider($name)){
			return $this->routeProviders[$name];
		}
		else return null;
	}
	
	public function getProviders(){
		return $this->routeProviders;
	}
	
	protected function getNamedRoute($name){
		
		$providers = $this->getProviders();
		
		if(sizeof($providers)){
			$providers = array_reverse($providers);
			
			foreach ($providers as $provider){
				$route = $provider->getRouteByName($name);
				if($route) return $route;
			}
		}
		
		return null;
	}
}