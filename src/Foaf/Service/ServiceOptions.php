<?php
namespace Foaf\Service;

use Zend\Stdlib\ParameterObjectInterface;

class ServiceOptions implements ParameterObjectInterface{
    
    protected $options = array();
    
    public function __construct(array $options)
    {
        $this->options = $options;
    }
    
	public function __get($key) {
		return $this->__isset($key) ? $this->options[$key] : null;
	}

	public function __isset($key) {
		return isset($this->options[$key]);
	}

	public function __set($key, $value) {
		$this->options[$key] = $value;
	}

	public function __unset($key) {
		unset($this->options[$key]);
	}
}