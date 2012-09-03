<?php
namespace Foaf\Test\Acl;

use Foaf\Test\InputFilter\TestAsset\TestDelegate;

use Foaf\InputFilter\Configurator\Delegate\ChildInputFilterDetector;

use Foaf\InputFilter\Configurator\Delegate\ParentInputFilterDetector;

use Zend\InputFilter\Input;

use Foaf\InputFilter\InputFilter;

use Foaf\InputFilter\Configurator;
use Foaf\InputFilter\Configurator\Delegate\ConfigurationAggregate;

class ConfiguratorTest extends \PHPUnit_Framework_TestCase{
    
    protected $myModel = array(
        'myprop' => array('required' => false)
    );
    
    protected $extModel = array(
        'extprop' => array('required' => false)
    );
    
    public function testSetDefaultInputFilterClass()
    {
        $class = 'Foaf\InputFilter\InputFilter';
        
        $configurator = new Configurator();
        $configurator->setDefaultInputFilterClass($class);
        
        $this->assertEquals(
            $class,
            $configurator->getDefaultInputFilterClass()
        );
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetInvalidDefaultInputFilterClass()
    {
        $class = 'Foaf\InvalidInputFilter';
    
        $configurator = new Configurator();
        $configurator->setDefaultInputFilterClass($class);
    }
    
    public function testConfigurationAggregateDelegate()
    {
        $configurator = $this->getPreConfigured(array(
            'mymodel' => $this->myModel
        ));
        
        $inputFilter = $configurator->configure('mymodel');
        
        $this->assertFalse(
            $inputFilter->get('myprop')->isRequired()        
        );
    }
    
    public function testParentDetectorDelegate()
    {

        $config = array(
            'mymodel' => $this->myModel,
            'extmodel' => $this->extModel
        );
        
        $config['mymodel']['type'] = 'extmodel';
        
        $configDelegate = new ConfigurationAggregate(array('config' =>
            $config
        ));
        
        $parentDelegate = new ParentInputFilterDetector();
    
        $configurator = new Configurator();
        $configurator->addDelegate($configDelegate);
        $configurator->addDelegate($parentDelegate);
        
        $inputFilter = $configurator->configure('mymodel');
    
        $this->assertTrue(
            $inputFilter->has('extprop')
        );
    }
    
    public function testChildDetectorDelegate()
    {
        $config = array(
            'mymodel' => $this->myModel,
            'extmodel' => $this->extModel
        );
        
        $config['mymodel']['myprop']['type'] = 'extmodel';
        
        $configDelegate = new ConfigurationAggregate(array('config' =>
            $config
        ));
        
        $childDelegate = new ChildInputFilterDetector();
        
        $configurator = new Configurator();
        $configurator->addDelegate($configDelegate);
        $configurator->addDelegate($childDelegate);
        
        $inputFilter = $configurator->configure('mymodel');
        
        $this->assertTrue(
            $inputFilter->has('myprop') && $inputFilter->get('myprop')->has('extprop')
        );
    }
    
    public function testGetSpecifications()
    {
        $configurator = $this->getPreConfigured(array(
            'mymodel' => $this->myModel
        ));
        
        $this->assertEquals(
            $this->myModel,
            $configurator->getSpecifications('mymodel')        
        );
    }
    
    public function testFinalizeDelegate()
    {
        $finalizer = new TestDelegate();
        $finalizer->finalizeHandler = function($configurator, $name, $inputFilter){
            $inputFilter->add(new Input(), 'extrainput');
        };
        
        $configurator = $this->getPreConfigured(array(
            'mymodel' => $this->myModel
        ));
        
        $configurator->addDelegate($finalizer);
        
        $this->assertTrue(
            $configurator->configure('mymodel')->has('extrainput')        
        );
    }
    
    public function testNamedDelegate()
    {
        $config = array(
            'mymodel' => $this->myModel,
            'extmodel' => $this->myModel
        );
        
        $mymodelDelegate = new TestDelegate();
        $mymodelDelegate->getHandler = function($configurator, $name){
            return array($name => array('extrainput' => array('required' => false)));
        };
        
        $configurator = $this->getPreConfigured($config);
        $configurator->addDelegate($mymodelDelegate, 'mymodel');
        
        $this->assertTrue(
            $configurator->configure('mymodel')->has('extrainput')
        );
        
        $this->assertFalse(
            $configurator->configure('extmodel')->has('extrainput')
        );
    }
    
    protected function getPreConfigured($config)
    {
        $configDelegate = new ConfigurationAggregate(array('config' =>
                $config
        ));
        
        $configurator = new Configurator();
        $configurator->addDelegate($configDelegate);
        
        return $configurator;
    }
}