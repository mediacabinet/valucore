<?php
namespace Foaf\Router\Http;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Mvc\Router\Http\Part;
use Zend\Mvc\Router\RoutePluginManager;
use Zend\Mvc\Router\Exception;
use Zend\Mvc\Router\PriorityList;

class DynamicPart extends Part
{
    public function __construct($route, $mayTerminate, RoutePluginManager $routePlugins, Traversable $childRoutes = null)
    {
        parent::__construct($route, $mayTerminate, $routePlugins);
        
        if($childRoutes !== null){
            $this->setChildRoutes($childRoutes);
        }
    }
    
    public function setChildRoutes(Traversable $childRoutes){
        $this->childRoutes = $childRoutes;
    }
    
    public static function factory($options = array())
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (!is_array($options)) {
            throw new Exception\InvalidArgumentException(__METHOD__ . ' expects an array or Traversable set of options');
        }
        
        if (!isset($options['route'])) {
            throw new Exception\InvalidArgumentException('Missing "route" in options array');
        }
    
        if (!isset($options['route_plugins'])) {
            throw new Exception\InvalidArgumentException('Missing "route_plugins" in options array');
        }
    
        if (!isset($options['may_terminate'])) {
            $options['may_terminate'] = false;
        }
        
        if(!isset($options['child_routes'])){
            $options['child_routes'] = null;
        }

        if ($options['child_routes'] && !($options['child_routes'] instanceof Traversable)) {
            throw new Exception\InvalidArgumentException(__METHOD__ . ' expects an array or Traversable set of options');
        }
    
        return new static($options['route'], $options['may_terminate'], $options['route_plugins'], $options['child_routes']);
    } 
}