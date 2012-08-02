<?php
namespace Foaf;

/**
 * URN handler
 * 
 * Urn (Unified Resource Name) class handles URN strings
 * as defined in RFC2141
 * 
 * @link http://tools.ietf.org/html/rfc2141
 * @author juhasuni
 *
 */
class Urn{
	
	const SCHEME = 'scheme';
	
	const NID = 'nid';
	
	const NSS = 'nss';
	
	const DELIMITER = ':';
	
	private static $_regex = array(
		'scheme'		=> '[uU][rR][nN]',
		'part'			=> '[^:]+:',
		'letNum' 		=> 'a-zA-Z0-9',
		'charReserved' 	=> '"%/?\\#',
		'charOther' 	=> "()+,\\-.:=@;\$_!*'",
		'encoded' 		=> '%[a-fA-F0-9]{2}',
		'letNumHyp'		=> null,
		'trans'			=> null,
		'urnChars'		=> null,
		'nid'			=> null,
		'nss'			=> null,
		'initialized'	=> false
	);
	
	protected $_urn;
	
	public function __construct($urn){
		$this->_urn = $urn;
	}
	
	public function normalize(){
		$this->_urn = $this->getNormalized();
	}
	
	public function getNormalized(){
		$scheme = strtolower($this->getScheme());
		$nid 	= strtolower($this->getNid());
		$nss 	= preg_replace_callback(
			'#'.self::_getRegex('encoded').'#', 
			'strtolower', 
			$this->getNss()
		);
		
		return $scheme . ':' . $nid . ':' . $nss;
	}
	
	public function isValidScheme(){
		return preg_match('#^' . self::_getRegex('scheme') . self::DELIMITER . '#');
	}
	
	public function isValidNid(){
		return preg_match('#^' .self::_getRegex('part') . self::_getRegex('nid') . '#', $this->_urn);
	}
	
	public function isValidNss(){
		return preg_match('#^(' . self::_getRegex('part') . '){2}' . self::_getRegex('nss') . '#', $this->_urn);
	}
	
	public function isValid(){
		return preg_match(
			'#' . self::_getRegex('scheme') . self::DELIMITER . self::_getRegex('nid') . self::DELIMITER . self::_getRegex('nss') . '#', 
			$this->_urn
		);
	}
	
	public function getScheme(){
		if(preg_match('#^([^'.self::DELIMITER.']+)'.self::DELIMITER.'#', $this->_urn, $matches)){
			return $matches[1];
		}
		else return null;
	}
	
	public function getNid(){
		if(preg_match('#^' . self::_getRegex('part') . '(' . self::_getRegex('nid') . ')#', $this->_urn, $matches)){
			return $matches[1];
		}
		else return null;
	}
	
	public function getNss(){
		if(preg_match('#^(?:' . self::_getRegex('part') . '){2}(' . self::_getRegex('nss') . ')#', $this->_urn, $matches)){
			return $matches[1];
		}
		else return null;
	}
	
	public function __toString(){
		return $this->_urn;
	}
	
	final protected static function _getRegex($regexName){
		if (!self::$_regex['initialized']) {
			
			self::$_regex['letNumHyp'] 
				= self::$_regex['letNum'] . '\-';
				
			self::$_regex['nid'] 
				= '[' . self::$_regex['letNum'] . ']' . '[' . self::$_regex['letNumHyp'] . ']{1,31}';
				
			self::$_regex['trans'] 
				= self::$_regex['letNum'] . self::$_regex['charOther'] . self::$_regex['charReserved'];
				
			self::$_regex['urnChars']
				= '[' . self::$_regex['trans'] . ']|(?:' . self::$_regex['encoded'] . ')'; 
				
			self::$_regex['nss']
				= '(?:' . self::$_regex['urnChars'] . ')+';
			
			self::$_regex['initialized'] = true;
		}
		
		if(isset(self::$_regex[$regexName])){
			return self::$_regex[$regexName];
		}
		else{
			throw new \Exception('Unknown regexp '.$regexName);
		}
	}
}