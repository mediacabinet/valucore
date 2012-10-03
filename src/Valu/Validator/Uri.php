<?php
namespace Valu\Validator;

use Zend\Validator\AbstractValidator;

class Uri extends AbstractValidator
{
	
	const INVALID = 'url_invalid';
	
	const NOT_URI = 'not_url';
	
	protected $_messageTemplates = array(
		self::INVALID	=> "Invalid type given. String expected",
		self::NOT_URI 	=> "'%value%' is not a valid URI"
	);
	
	public function isValid($value)
	{
		if (!is_string($value)) {
			$this->error(self::INVALID);
			return false;
		}
		
		$this->setValue($value);

		$uri = new \Zend\Uri\Uri($value);
		return $uri->isValid();
	}
}