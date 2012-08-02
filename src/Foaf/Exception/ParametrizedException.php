<?php
namespace Foaf\Exception;

class ParametrizedException extends \Exception{
	
	protected $rawMessage = '';
	
	protected $vars = array();

	public function __construct($message, array $vars = array(), $code = 0, $previous = null){
		
		$this->rawMessage = $message;
		
		$this->setVars($vars);
		
		if(sizeof($vars)){
			$keys = array_keys($vars);
			$keys = array_map(
				array($this, 'escapeVar'),
				$keys
			);
			
			$message = str_replace(
				$keys, 
				array_values($vars), 
				$message
			);
		}
		
		parent::__construct($message, $code, $previous);
	}
	
	public function setVars(array $vars){
		$this->vars = $vars;
	}
	
	public function getVars(){
		return $this->vars;
	}
	
	protected final function escapeVar($var){
		return '%' . $var . '%';
	}
}