<?php
namespace ValuApp\Service;

use Valu\Service\AbstractService;

class Rest extends AbstractService{
    
    const SERVER_DELIMITER = '/';
    
    protected $optionsClass = 'Valu\Service\Broker\Rest\Options';
    
    public function __construct($config){
        $this->setConfig($config);
    }
    
    public static function version()
    {
        return '0.1';
    }
    
    /**
     * Executes service request via HTTP REST interface
     * 
     * @param string $service		Service name, optionally preceeded by server name
     * @param string $operation		Operation
     * @param array $args			Arguments
     */
    public function exec($service, $operation, $args = null){
        
        $client = $this->createHttpClient($service, $operation, $args);
        
        /**
         * Perform HTTP request
         */
        $client->send();
        
        /**
         * Fetch HTTP response and format final response based on
         * its Content-Type
         */
        $response = $client->getResponse();
        
        if($response->getStatusCode() == 200){
            if($response->header()->get('Content-Type') == 'application/json'){
                return \Zend\Json\Decoder::decode($response->getBody(), \Zend\Json\Json::TYPE_ARRAY);
            }
            else{
                return $response->getBody();
            }
        }
        else{
            return false;
        }
    }
    
    public function delay($id, $priority, $service, $operation, $args = null){
        // add to queue
    }
    
    public function remove($id){
        // remove from queue
    }
    
    public function flush(){
        // flush queue
    }
    
    /**
     * Prepare a new HTTP client
     * 
     * @param string $service
     * @param string $operation
     * @param array $args
     * 
     * @return string
     */
    protected function createHttpClient($service, $operation, $args = null){
        
        /**
         * Parse name of the server
         */
        $a = explode(self::SERVER_DELIMITER, $service);
        
        if(count($a) > 1){
            $service = array_pop($a);
            $serverName = implode(self::SERVER_DELIMITER, $a);
        }
        else{
            $serverName = $this->getOption('default_server');
        }
        
        $server = $this->getOptions()->getServer($serverName);
        
        if(!$server){
            throw new \Exception('Server '.$serverName.' not found');
        }
        
        $url = $server['route']->assemble(array(
        	'service' => $service,
        	'operation' => $operation
        ));
        
        $uri = new \Zend\Uri\Http($url);
        
        if(is_array($args) && sizeof($args)){
            $uri->setQuery($args);
        }
        
        $client = new \Zend\Http\Client($uri);
        $client->setAdapter('Zend\Http\Client\Adapter\Curl');
        
        return $client;
    }
}