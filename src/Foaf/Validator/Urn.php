<?php
namespace Foaf\Validator;

use Zend\Validator\AbstractValidator;

class Urn extends AbstractValidator
{
	
	const INVALID = 'urnInvalid';
	
	const NOT_URN = 'notUrn';
	
	protected $_messageTemplates = array(
		self::INVALID	=> "Invalid type given. String expected",
		self::NOT_URN 	=> "'%value%' is not a URN"
	);
	
	public function isValid($value)
	{
		if (!is_string($value) && !is_int($value) && !is_float($value)) {
			$this->_error(self::INVALID);
			return false;
		}
		
		$this->_setValue($value);

		$urn = new \Foaf\Urn($value);
		
		if(!$urn->isValid()){
			$this->_error(self::NOT_URN);
			return false;
		}
		
		return true;
	}
}