<?php
namespace ValuTest\Service\Definition\Driver;

use Valu\Version\SoftwareVersion,
	Valu\Version\Exception\InvalidVersionException,
	ValuTest\Service\TestAsset\MockService,
	Valu\Service\Definition\Driver\PhpDriver;

class PhpDriverTest extends \PHPUnit_Framework_TestCase
{
    static $mock;
    
    static $definition;
    
    public static function setUpBeforeClass()
	{
	    self::$mock = new MockService();
	    self::$mock->setDriver(new PhpDriver());
	    self::$definition = self::$mock->define();
	}
	
	public function testOperationNoArgs()
	{
	    $this->operationTest(
            'deleteAll',
            array(
                'params' => array(),
                'meta' => array()
            )
	    );
	}
	
	public function testOperationOneArg()
	{
	    $this->operationTest(
	            'delete',
	            array(
                    'params' => array(
                        'id' => array(
                            'optional' => false,
                            'has_default_value' => false,
                            'default_value' => null
                        )
                    ),
                    'meta' => array()
	            )
	    );
	}
	
	public function testOperationOptionalArg()
	{
	    $this->operationTest(
            'find',
            array(
                'params' => array(
                    'query' => array(
                        'optional' => false,
                        'has_default_value' => false,
                        'default_value' => null
                    ),
                    'default' => array(
                        'optional' => true,
                        'has_default_value' => true,
                        'default_value' => null
                    )
                ),
                'meta' => array()
            )
        );
	}
	
	public function testIgnoreUnderscorePrefix(){
	    $this->assertFalse(self::$definition->hasOperation('__invoke'));
	}
	
	public function testIgnoreAsDocumented(){
	    $this->assertFalse(self::$definition->hasOperation('nonServiceMethod'));
	}
	
	public function testIgnoreNonPublic(){
	    $this->assertFalse(self::$definition->hasOperation('internalProtected'));
	}
	
	public function testIgnoreInheritedClassAsDocumented(){
	    $this->assertFalse(self::$definition->hasOperation('internalPublic'));
	}

    public function operationTest($name, $specs){
        $this->assertEquals(
            $specs,
            self::$definition->defineOperation($name)
        );
    }
}