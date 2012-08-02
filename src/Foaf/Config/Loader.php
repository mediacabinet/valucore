<?php
namespace Foaf\Config;

class Loader
{
	const TYPE_INI = 'ini';
	const TYPE_XML = 'xml';
	
	protected $directory = null;
	
	protected $defaultSection = null;

	public function __construct($directory, $defaultSection){
		
		if(!is_readable($directory)){
			throw new \Exception('Directory '.$directory.' is not readable');
		}
		
		$this->directory = rtrim($directory, '/');
		
		$this->setDefaultSection($defaultSection);
	}
	
	public function getDirectory(){
		return $this->directory;
	}
	
	public function setDefaultSection($section){
		$this->defaultSection = $section;
	}
	
	public function getDefaultSection(){
		return $this->defaultSection;
	}
	
	public function getConfig($file, $section = null){
		
		if(is_null($section)){
			$section = $this->getDefaultSection();
		}
		
		$filetype 	= $this->parseFiletype($file);
		$file 		= $this->getDirectory() . DIRECTORY_SEPARATOR . $file;
		
		if($filetype == self::TYPE_INI){
			$config = new \Zend\Config\Ini(
				$file,
				$section
			);
		}
		else{
			$config = new \Zend\Config\Xml(
				$file,
				$section
			);
		}
		
		return $config;
	}
	
	protected function parseFiletype($file){
		$filename 	= basename($file);
		$array 		= explode('.', $filename);
		$filetype 	= array_pop($array);
		
		if(!in_array($filetype, array(self::TYPE_INI, self::TYPE_XML))){
			throw new \Exception('Illegal config file type, only "ini" and "xml" are supported.');
		}
		else return $filetype;
	}
}
