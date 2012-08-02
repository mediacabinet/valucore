<?php
namespace Foaf\Navigation\Page\Partial;

use Foaf\Navigation\Page\PartialInterface,
	Zend\Navigation\Page\AbstractPage;

class Dummy extends AbstractPage implements PartialInterface{
	public function getHref(){
		return '#';
	}
}