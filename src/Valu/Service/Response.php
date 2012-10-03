<?php
namespace Valu\Service;

interface Response extends \Zend\Stdlib\MessageInterface
{

    public function __toString();
}