<?php
namespace Valu\Validator;

use Zend\Validator\AbstractValidator;

/**
 * UUID validator
 * 
 */
class Uuid extends AbstractValidator
{
	
	const INVALID = 'invalid';

	private $regExp = '/[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}/i';
	
	private $length = 36;
	
	protected $messageTemplates = array(
		self::INVALID	=> 'Value is not a valid UUID'
	);
	
	public function isValid($value)
	{
	    $this->setValue($value);
	    
	    if (strlen($value) !== $this->length
	        || !preg_match($this->regExp, $value)) {
	        
			$this->error(self::INVALID);
			return false;
		}

		return true;
	}
}