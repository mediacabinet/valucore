<?php
namespace ValuTest\Model;

use Valu\Model\ArrayAdapter;
use ValuTest\Model\TestAsset\MockModel;

class ArrayAdapterTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var ArrayAdapter
     */
    private $arrayAdapter;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->arrayAdapter = new ArrayAdapter();
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->arrayAdapter = null;
        parent::tearDown();
    }
    
    public function testFromArray()
    {
        $data = array(
            'name'   => 'Test Mock',
            'meta'   => array('data'=>'metadata'),
        );
        
        $adapter  = new ArrayAdapter();
        $model    = $this->newMock(array());
        
        $adapter->fromArray($model, $data);
        
        $actual = array();
        foreach(array_keys($data) as $prop) {
            $actual[$prop] = $model->{$prop};
        }
        
        $this->assertEquals(
            $data,
            $actual     
        );
    }
    
    public function testFromArrayWithChildProperty()
    {
        MockModel::$arrayAdapter = $this->arrayAdapter;
        
        $data = array(
            'id'   => 'Child ID',
            'name' => 'Child name'
        );
        
        $newName = 'Child name changed';
        
        $child = $this->newMock($data);
        $model = $this->newMock(array('child' => $child));
        
        $this->arrayAdapter->fromArray($model, array('child' => array('name' => $newName)) );
        
        $data['name'] = $newName;

        $this->assertEquals(
            $data,
            array(
                'id' => $child->id,
                'name' => $child->name        
            )
        );
    }
    
    public function testToArrayWithoutExtractionParam()
    {
        $data = array(
            'id'     => 'mock123',
            'name'   => 'Test Mock',
            'meta'   => array('data'=>'metadata'),
            'child'  => null
        );
    
        $model = $this->newMock($data);
    
        $this->assertEquals(
                $data,
                $this->arrayAdapter->toArray($model)
        );
    }
    
    public function testToArrayUsingArrayAsExtractionParam()
    {
        $data = array(
            'id'     => 'mock123',
            'name'   => 'Test Mock',
            'meta'   => array('data'=>'metadata'),
            'child'  => null
        );
    
        $model = $this->newMock($data);
    
        $this->assertEquals(
            ['id' => $data['id'], 'name' => $data['name']],
            $this->arrayAdapter->toArray($model, ['id' => true, 'name' => true])
        );
    }

    public function testToArrayUsingWildcharAsExtractionParam()
    {
        $data = array(
            'id'     => 'mock123',
            'name'   => 'foo',
            'meta'   => array('settings'=>['enabled' => true]),
            'child'  => null
        );

        $model = $this->newMock($data);

        $this->assertEquals(
            ['id' => $data['id'], 'name' => $data['name'], 'meta' => $data['meta']],
            $this->arrayAdapter->toArray($model, ['*' => true, 'child' => false])
        );
    }

    public function testToArrayUsingWildcharWithAssociativeArray()
    {
        $data = array(
            'id'     => 'mock123',
            'name'   => 'foo',
            'meta'   => array('settings'=>['enabled' => true]),
            'child'  => null
        );

        $model = $this->newMock($data);

        $this->assertEquals(
            ['meta' => $data['meta']],
            $this->arrayAdapter->toArray($model, ['meta' => true])
        );
    }
    
    public function testToArrayUsingStringAsExtractionParam()
    {
        $data = array(
            'id'     => 'mock123',
            'name'   => 'Test Mock',
        );
    
        $model = $this->newMock($data);
    
        $this->assertEquals(
            ['name' => $data['name']],
            $this->arrayAdapter->toArray($model, 'name')
        );
    }
    
    public function testToArrayWithRecursion()
    {
        $data = array(
            'meta' => array(
                'data'=>'metadata', 
                'keywords'=>'kw'
            ),
        );

        $model = $this->newMock($data);
        
        $this->assertEquals(
            array('meta' => array('data' => 'metadata')),
            $this->arrayAdapter->toArray($model, array('meta' => array('data' => true)))
        );
    }
    
    public function testToArrayWithFullRecursion()
    {
        $data = array(
            'meta' => array(
                'data'=>'metadata',
                'keywords'=>'kw'
            ),
        );
        
        $model = $this->newMock($data);
        
        $this->assertEquals(
            array('meta' => $data['meta']),
            $this->arrayAdapter->toArray($model, array('meta' => true))
        );
    }
    
    public function testToArrayTriggersPreEvent()
    {
        $data = array(
            'name' => 'Mock model'
        );
        
        $event = null;
        
        $model = $this->newMock($data);
        $this->arrayAdapter->getEventManager()->attach('pre.toArray', function($e) use(&$event) {
            $event = $e;
        });
        
        $this->arrayAdapter->toArray($model, ['name' => true]);
        
        $this->assertNotNull($event);
        $this->assertSame($model, $event->getParam('object'));
        $this->assertEquals(['name' => true], $event->getParam('extract'));
    }
    
    public function testToArrayTriggersExtractEvent()
    {
        $data = array(
            'name' => 'Mock model',
        );
    
        $eventParams = null;
    
        $model = $this->newMock($data);
        $this->arrayAdapter->setExtractScalarsSilently(false);
        $this->arrayAdapter->getEventManager()->attach('extract', function($e) use(&$eventParams) {
            $eventParams = $e->getParams()->getArrayCopy();
        });
    
        $this->arrayAdapter->toArray($model, ['name' => true]);

        $this->assertNotNull($eventParams);
        $this->assertEquals('name', $eventParams['spec']);
    }
    
    public function testToArrayTriggersPostEvent()
    {
        $data = array(
            'name' => 'Mock model'
        );
        
        $event = null;
        
        $model = $this->newMock($data);
        $this->arrayAdapter->getEventManager()->attach('post.toArray', function($e) use(&$event) {
            $event = $e;
        });
        
        $this->arrayAdapter->toArray($model, ['name' => true]);
        
        $this->assertNotNull($event);
        $this->assertSame($model, $event->getParam('object'));
        $this->assertEquals(['name' => true], $event->getParam('extract'));
        $this->assertEquals($data, $event->getParam('data')->getArrayCopy());
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