<?php
namespace ValuTest\Model;

use PHPUnit_Framework_TestCase;
use Valu\Model\ArrayAdapter\ModelListener;
use Zend\EventManager\Event;
use Valu\Model\ArrayAdapter;
use ValuTest\Model\TestAsset\MockModel;

/**
 * ModelListener test case.
 */
class ModelListenerTest extends PHPUnit_Framework_TestCase
{

    /**
     *
     * @var ModelListener
     */
    private $modelListener;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->modelListener = new ModelListener();
        
        $adapter = new ArrayAdapter();
        MockModel::$arrayAdapter = $adapter;
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated ModelListenerTest::tearDown()
        $this->modelListener = null;
        MockModel::$arrayAdapter = null;
        
        parent::tearDown();
    }

    /**
     * Tests ModelListener->__invoke()
     */
    public function testExtractsNamedProperties()
    {
        $modelData = ['name' => 'Mock model', 'id' => 'mockmodelid'];
        $model = $this->newMock($modelData);
        
        $data = new \ArrayObject([
            'object' => $model        
        ]);
        
        $params = new \ArrayObject([
            'data' => $data,
            'spec' => 'object',
            'extract' => ['name' => true, 'id' => true]       
        ]);
        
        $event = new Event('extract', $this, $params);
        $this->modelListener->__invoke($event);
        
        $this->assertEquals(
            ['object' => $modelData],
            $data->getArrayCopy()
        );
    }
    
    public function testExtractsOnlyId()
    {
        $modelData = ['id' => 'mockmodelid'];
        $model = $this->newMock($modelData);
        
        $data = new \ArrayObject([
            'object' => $model
            ]);
        
        $params = new \ArrayObject([
            'data' => $data,
            'spec' => 'object',
            'extract' => true
            ]);
        
        $event = new Event('extract', $this, $params);
        $this->modelListener->__invoke($event);
        
        $this->assertEquals(
                ['object' => $modelData['id']],
                $data->getArrayCopy()
        );
    }
    
    private function newMock(array $data)
    {
        $mock = new MockModel();
    
        foreach ($data as $key => $value) {
            $mock->{$key} = $value;
        }
        
        return $mock;
    }
}

