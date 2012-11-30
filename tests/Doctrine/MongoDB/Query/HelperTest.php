<?php
namespace Valu\Test\Acl;

use Doctrine\ODM\MongoDB\DocumentRepository;

use Valu\Doctrine\MongoDB\Query\Helper;

class HelperTest extends \PHPUnit_Framework_TestCase{
    
    public function testIsEmptyQueryParamWithArray()
    {
        $helper = new Helper();
        $this->assertTrue($helper->isEmptyQueryParam(array()));
    }
    
    public function testIsEmptyQueryParamWithString()
    {
        $helper = new Helper();
        $this->assertTrue($helper->isEmptyQueryParam(""));
    }
    
    public function testIsEmptyQueryParamWithSpace()
    {
        $helper = new Helper();
        $this->assertTrue($helper->isEmptyQueryParam(" "));
    }
    
    public function testIsEmptyQueryParamWithInt()
    {
        $helper = new Helper();
        $this->assertFalse($helper->isEmptyQueryParam(0));
    }
}