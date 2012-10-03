<?php
namespace Valu\Router\Route;

use Zend\Stdlib\RequestDescription as Request,
	Zend\Mvc\Router\Route;

interface RouteProvider{
	
	/**
	 * Match request against provided routes
	 * 
	 * @param Request $request
	 * @param int|null $pathOffset
	 * @return \Zend\Mvc\Router\Http\RouteMatch
	 */
	public function match(Request $request, $pathOffset = null);
	
	/**
	 * Get route by name
	 * 
	 * @param string $name
	 * @return Route
	 */
	public function getRouteByName($name);
}