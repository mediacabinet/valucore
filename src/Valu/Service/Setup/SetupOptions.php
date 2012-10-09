<?php
namespace Valu\Service\Setup;

use Zend\Stdlib\AbstractOptions;

class SetupOptions extends AbstractOptions{
    
    /**
     * Module version
     * 
     * @var string
     */
    protected $version = '';

	/**
	 * @return the $version
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}
}