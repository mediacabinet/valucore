<?php
namespace Foaf\Validator;

use Zend\Validator\AbstractValidator;

/**
 * Phone number validator
 * 
 */
class PhoneNumber extends AbstractValidator
{
	
	const INVALID = 'invalid';
	
	const NOT_UNIVERSAL = 'not_universal';
	
	private $universal = false;
	
	private $langRegExp = '^(\+[0-9()]+)';
	
	private $re = array(
	    'lang'         => '^(\+\(?[0-9]+\)?)',
        'part'         => '\(?[0-9]+\)?',
        'separator'    => '(\-| )?'
    );
	
	protected $_messageTemplates = array(
		self::INVALID	=> 'Invalid phone number. Valid phone number may contain only numbers, dashes and parentheses, optinally preceeded by + sign.',
        self::NOT_UNIVERSAL => "Phone number doesn't contain language code.",
	);
	
	public function isValid($value)
	{
	    $this->setValue($value);
	    
	    if (!preg_match('/'.$this->re['lang'].'?('.$this->re['separator'].$this->re['part'].')+$/', $value)) {
			$this->error(self::INVALID);
			return false;
		}
		
		if($this->getUniversal() && !preg_match('/'.$this->re['lang'].'/', $value)){
		    $this->error(self::NOT_UNIVERSAL);
		    return false;
		}
		
		return true;
	}
	
	/**
	 * Require universal phone numbers (with language code)
	 * 
	 * @param boolean $universal
	 */
	public function setUniversal($universal)
	{
	    $this->universal = (bool) $universal;
	}
	
	/**
	 * Is validator set to accept only universal phone numbers
	 * 
	 * @return boolean
	 */
	public function getUniversal()
	{
	    return $this->universal;
	}
}