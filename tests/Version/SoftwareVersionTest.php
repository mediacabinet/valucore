<?php
namespace Valu\Test\Version;

use Valu\Version\SoftwareVersion,
	Valu\Version\Exception\InvalidVersionException;

class SoftwareVersionTest extends \PHPUnit_Framework_TestCase
{
	public function testGetVersion(){
		$version = new SoftwareVersion('1.0');
		$this->assertEquals('1.0', $version->getVersion());
	}
	
	public function testSetGetVersion(){
	    $version = new SoftwareVersion('1.0');
	    $version->setVersion('1.1');
	    $this->assertEquals('1.1', $version->getVersion());
	}
	
	/**
	 * @expectedException \Valu\Version\Exception\InvalidVersionException
	 */
	public function testSetInvalid(){
	    $version = new SoftwareVersion('1-0');
	}

	public function testIsValid(){
		$this->assertTrue(SoftwareVersion::isValid('1a'));
		$this->assertTrue(SoftwareVersion::isValid('1.1a'));
		$this->assertTrue(SoftwareVersion::isValid('1.1-a'));
		$this->assertTrue(SoftwareVersion::isValid('1.1-a1'));
		$this->assertTrue(SoftwareVersion::isValid('1.11.0-b1'));
		
		$this->assertFalse(SoftwareVersion::isValid('a'));
		$this->assertFalse(SoftwareVersion::isValid('1.a.1'));
		$this->assertFalse(SoftwareVersion::isValid('1$'));
	}
	
	public function testCompareTo(){
	    $version = new SoftwareVersion('1.0');
	    
	    $this->assertEquals(-1, $version->compareTo(1.1));
	    $this->assertEquals(-1, $version->compareTo('1.01'));
	    $this->assertEquals(-1, $version->compareTo('1.0.1'));
	    $this->assertEquals(-1, $version->compareTo('1.1a'));
	    
	    $this->assertEquals(+1, $version->compareTo('0.9.1'));
	    $this->assertEquals(+1, $version->compareTo('0.95'));
	    $this->assertEquals(+1, $version->compareTo('0'));
	    
	    $this->assertEquals(0, $version->compareTo(1.0));
	    $this->assertEquals(0, $version->compareTo('1.0'));
	    $this->assertEquals(0, $version->compareTo('1.0-devel'));
	}
	
	public function testLt(){
	    $version = new SoftwareVersion('2.99.99');
	    $this->assertTrue($version->isLt('2.100'));
	    $this->assertTrue($version->isLt('2.99.99.1'));
	    $this->assertTrue($version->isLt('03'));
	    $this->assertFalse($version->isLt('2'));
	}
	
	public function testGt(){
	    $version = new SoftwareVersion('0.0.1');
	    $this->assertTrue($version->isGt('0.0.0.1'));
	    
	    $version = new SoftwareVersion('1.9');
	    $this->assertTrue($version->isGt('0'));
	    $this->assertTrue($version->isGt('1'));
	    $this->assertTrue($version->isGt('1.8.9'));
	    $this->assertTrue($version->isGt('1.8.9.2a'));
	    $this->assertTrue($version->isGt('1.08'));
	}
	
	public function isEqualTo(){
	    $version = new SoftwareVersion('1.5');
	    
	    $this->assertTrue($version->isEqualTo('1.5'));
	    $this->assertTrue($version->isEqualTo('01.5'));
	    $this->assertTrue($version->isEqualTo('1.5a'));
	    $this->assertTrue($version->isEqualTo('1.5.0'));
	}
	
	public function testParse(){
	    $this->assertEquals(array(0,1,1), SoftwareVersion::parse('0.1.1'));
	    $this->assertEquals(array(2,1,1001), SoftwareVersion::parse('002.1a.1001'));
	    $this->assertEquals(array(0), SoftwareVersion::parse('0'));
	    $this->assertEquals(array(0), SoftwareVersion::parse('a'));
	}
	
	public function testGetNumeric(){
	    $version = new SoftwareVersion(1.5);
	    $this->assertEquals('1.5', $version->getNumeric());
	    
	    $version = new SoftwareVersion('1.0');
	    $this->assertEquals('1', $version->getNumeric());
	    
	    $version = new SoftwareVersion('1.01');
	    $this->assertEquals('1.1', $version->getNumeric());
	    
	    $version = new SoftwareVersion('1.09a');
	    $this->assertEquals('1.9', $version->getNumeric());
	}
}