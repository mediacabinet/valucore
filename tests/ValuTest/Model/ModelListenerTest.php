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
        $this->modelListener = new ModelListener(['namespaces' => ['ValuTest\\']]);
        
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

    public function testExtractNamedProperties()
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
    
    public function testExtractOnlyId()
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

    public function testExtractUsingWildchar()
    {
        $adapter = new ArrayAdapter();
        $adapter->getEventManager()->attach('extract', $this->modelListener);
        MockModel::$arrayAdapter = $adapter;

        $modelData = ['name' => 'Grand Child'];
        $grandChild = $this->newMock($modelData);

        $modelData = ['name' => 'Child', 'child' => $grandChild];
        $child = $this->newMock($modelData);

        $modelData = ['name' => 'Parent', 'child' => $child];
        $parent = $this->newMock($modelData);

        $arrayData = $parent->toArray(['child' => ['*' => true, 'child' => ['name' => true]]]);
        $this->assertEquals(
            [
                'child' => [
                    'name' => 'Child',
                    'id' => '',
                    'meta' => [],
                    'child' => ['name' => 'Grand Child']]],
            $arrayData
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

