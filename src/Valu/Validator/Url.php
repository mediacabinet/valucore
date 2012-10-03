<?php
namespace Valu\Validator;

use Zend\Validator\AbstractValidator;

class Url extends AbstractValidator
{
	
	const INVALID = 'url_invalid';
	
	const NOT_URL = 'not_url';
	
	protected $_messageTemplates = array(
		self::INVALID	=> "Invalid type given. String expected",
		self::NOT_URL 	=> "'%value%' is not a valid URL"
	);
	
	public function isValid($value)
	{
		if (!is_string($value)) {
			$this->error(self::INVALID);
			return false;
		}
		
		$this->setValue($value);

		$uri = new \Zend\Uri\Uri($value);
		return $uri->getScheme() && $uri->getHost() && $uri->isValid();
	}
}