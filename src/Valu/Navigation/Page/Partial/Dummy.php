<?php
namespace Valu\Navigation\Page\Partial;

use Valu\Navigation\Page\PartialInterface,
	Zend\Navigation\Page\AbstractPage;

class Dummy extends AbstractPage implements PartialInterface{
	public function getHref(){
		return '#';
	}
}