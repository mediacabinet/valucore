<?php
namespace ValuTest\Model;

use PHPUnit_Framework_TestCase;
use Valu\Model\ArrayAdapter\DateFormatterListener;
use Zend\EventManager\Event;

/**
 * DateFormatterListener test case.
 */
class DateFormatterListenerTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @var DateFormatterListener
     */
    private $dateFormatterListener;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->dateFormatterListener = new DateFormatterListener();
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated DateFormatterListenerTest::tearDown()
        $this->dateFormatterListener = null;
        parent::tearDown();
    }
    
    public function testDefaultFormatIsISO()
    {
        $date = new \DateTime();
        
        $data = new \ArrayObject([
            'createdAt' => $date        
        ]);
        
        $params = new \ArrayObject([
            'data' => $data,
            'spec' => 'createdAt',
            'extract' => true
        ]);
        
        $event = new Event('extract', $this, $params);
        $this->dateFormatterListener->__invoke($event);
        
        $this->assertEquals(
            $date->format(DATE_ISO8601),
            $data['createdAt']);
    }
    
    public function testFormatAsOption()
    {
        $date = new \DateTime();
        
        $data = new \ArrayObject([
            'createdAt' => $date
        ]);
        
        $params = new \ArrayObject([
            'data' => $data,
            'spec' => 'createdAt',
            'extract' => true,
            'options' => ['date_formatter' => ['format' => DATE_ATOM]]
            ]);
        
        $event = new Event('extract', $this, $params);
        $this->dateFormatterListener->__invoke($event);
        
        $this->assertEquals(
            $date->format(DATE_ATOM),
            $data['createdAt']);
    }
    
}