<?php
namespace Valu\Doctrine\MongoDB\DocumentManager;

use Doctrine\MongoDB\Connection,
	Doctrine\Common\EventManager,
	Doctrine\ODM\MongoDB\Configuration;

class Factory{
	
	protected $connectionOptions;
	
	protected $ormConfig;
	
	protected $eventManager;
	
	public function __construct(array $connectionOptions, Configuration $ormConfig, $eventManager = null){
		$this->setConnectionOptions($connectionOptions);
		$this->setOrmConfig($ormConfig);
		
		if($eventManager instanceof EventManager){
			$this->setEventManager($eventManager);
		}
	}
	
	/**
	 * @return the $connectionOptions
	 */
	public function getConnectionOptions() {
		return $this->connectionOptions;
	}

	/**
	 * @param field_type $connectionOptions
	 */
	public function setConnectionOptions($connectionOptions) {
		$this->connectionOptions = $connectionOptions;
	}

	/**
	 * @return the $ormConfig
	 */
	public function getOrmConfig() {
		return $this->ormConfig;
	}

	/**
	 * @param field_type $ormConfig
	 */
	public function setOrmConfig(Configuration $ormConfig) {
		$this->ormConfig = $ormConfig;
	}

	/**
	 * @return the $eventManager
	 */
	public function getEventManager() {
		return $this->eventManager;
	}

	/**
	 * @param field_type $eventManager
	 */
	public function setEventManager(EventManager $eventManager) {
		$this->eventManager = $eventManager;
	}

	/**
	 * Factory method for creating new document manager instance
	 * 
	 * @param Configuration|null $ormConfig
	 * @param EventManager|null $eventManager
	 */
	public function create($ormConfig = null, $eventManager = null){
		
		$ormConfig = ($ormConfig instanceof Configuration)
			? $ormConfig
			: $this->getOrmConfig();
			
		$eventManager = ($eventManager instanceof EventManager)
			? $eventManager
			: $this->getEventManager();
		
		$server = 'localhost';
		
		if(isset($this->connectionOptions['server'])){
			$server = $this->connectionOptions['server'];
			unset($this->connectionOptions['server']);
		}
		
		$connection = new Connection(
			$server,
			$this->connectionOptions
		);
			
		$em = \Doctrine\ODM\MongoDB\DocumentManager::create($connection, $ormConfig, $eventManager);
		return $em;
	}
}