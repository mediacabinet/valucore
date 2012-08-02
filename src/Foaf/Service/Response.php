<?php
namespace Foaf\Service;

interface Response extends \Zend\Stdlib\MessageDescription{
    public function __toString();
}