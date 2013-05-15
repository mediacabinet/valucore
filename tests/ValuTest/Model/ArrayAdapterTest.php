<?php
namespace ValuTest\Model;

use Valu\Model\ArrayAdapter;
use ValuTest\Model\TestAsset\MockModel;

class ArrayAdapterTest extends \PHPUnit_Framework_TestCase
{

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
        $adapter = new ArrayAdapter();
        MockModel::$arrayAdapter = $adapter;
        
        $data = array(
            'id'   => 'Child ID',
            'name' => 'Child name'
        );
        
        $newName = 'Child name changed';
        
        $child = $this->newMock($data);
        $model = $this->newMock(array('child' => $child));
        
        $adapter->fromArray($model, array('child' => array('name' => $newName)) );
        
        $data['name'] = $newName;

        $this->assertEquals(
            $data,
            array(
                'id' => $child->id,
                'name' => $child->name        
            )
        );
    }
    
    public function testToArray()
    {
        $data = array(
            'id'     => 'mock123',
            'name'   => 'Test Mock',
            'meta'   => array('data'=>'metadata'),
            'child'  => null
        );
    
        $adapter = new ArrayAdapter();
        $model = $this->newMock($data);
    
        $this->assertEquals(
                $data,
                $adapter->toArray($model)
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
        
        $adapter = new ArrayAdapter();

        $this->assertEquals(
            array('meta' => array('data' => 'metadata')),
            $adapter->toArray($model, array('meta' => array('data' => true)))
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
        
        $adapter = new ArrayAdapter();
        
        $this->assertEquals(
            array('meta' => $data['meta']),
            $adapter->toArray($model, array('meta' => true))
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