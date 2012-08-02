<?php
namespace Foaf\Service\Broker\Rest;

use Zend\Stdlib\Options,
	Zend\Mvc\Router;

class Options extends Options{
    
    /**
     * Default server name
     * 
     * @var string
     */
    protected $defaultServer = '';
    
    /**
     * Array of servers
     * 
     * @var array
     */
    protected $servers = array();
    
    /**
     * Set default server name
     * 
     * @param string $server
     */
    public function setDefaultServer($server){
        $this->defaultServer = $server;
    }
    
    /**
     * Retrieve default server name
     * 
     * @return string
     */
    public function getDefaultServer(){
        return $this->defaultServer;
    }
    
    /**
     * Set server configurations
     * 
     * @param array|\Traversable $servers
     * @throws \InvalidArgumentException
     */
    public function setServers($servers){
        if(!is_array($servers) && !($servers instanceof \Traversable)){
            throw new \InvalidArgumentException('$$servers must be an array or implement Traversable interface');
        }
        
        $this->servers = array();
        
        foreach ($servers as $name => $specs){
            
            $url 	= $specs['route'];
            $route 	= new Router\Http\Segment($url);

            $this->servers[$name] = array(
            	'route' => $route
            );
        }
    }
    
    /**
     * Retrieve server configurations
     * 
     * @return array
     */
    public function getServers(){
        return $this->servers;
    }
    
    /**
     * Retrieve server specs by name
     * 
     * @param string $name
     * @return null|Array
     */
    public function getServerByName($name){
        return 	isset( $this->servers[$name])
        		? $this->servers[$name]
        		: null;
    }
}