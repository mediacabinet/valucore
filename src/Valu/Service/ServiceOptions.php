<?php
namespace Valu\Service;

class ServiceOptions extends \ArrayObject{

	public function __get($key) {
		return $this->offsetGet($key);
	}

	public function __isset($key) {
		return $this->offsetExists($key);
	}

	public function __set($key, $value) {
		$this->offsetSet($key, $value);
	}

	public function __unset($key) {
		$this->offsetUnset($key);
	}
}