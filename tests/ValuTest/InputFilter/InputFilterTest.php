<?php
namespace ValuTest\InputFilter;

use ValuTest\InputFilter\TestAsset\Input;
use Zend\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;
use Valu\InputFilter\InputFilter;

class InputFilterTest extends TestCase
{
    public function testInputInheritsServiceLocator()
    {
        $serviceLocator = new ServiceManager();
        $inputFilter = new InputFilter();
        
        $input = new Input('test');
        $inputFilter->add($input);
        
        $inputFilter->setMainServiceLocator($serviceLocator);
        
        $this->assertEquals($serviceLocator, $input->getServiceLocator());
    }
    
    public function testFilterChainInheritsServiceLocator()
    {
        $serviceLocator = new ServiceManager();
        
        $input = new Input('test');
        $input->getFilterChain()->getPluginManager()->setInvokableClass(
            'testfilter', 
            'ValuTest\InputFilter\TestAsset\TestFilter');
        
        $input->getFilterChain()->attachByName('testfilter');
        
        $inputFilter = new InputFilter();
        $inputFilter->add($input);
        
        // Inject service locator
        $inputFilter->setMainServiceLocator($serviceLocator);
        
        $this->assertEquals(
            $serviceLocator,
            $input->getFilterChain()->getFilters()->top()->getServiceLocator()->getServiceLocator());
    }
    
    public function testValidatorChainInheritsServiceLocator()
    {
        $serviceLocator = new ServiceManager();
        
        $input = new Input('test');
        $input->getValidatorChain()->getPluginManager()->setInvokableClass(
                'testvalidator',
                'ValuTest\InputFilter\TestAsset\TestValidator');
        
        $input->getValidatorChain()->addByName('testvalidator');
        
        $inputFilter = new InputFilter();
        $inputFilter->add($input);
        
        // Inject service locator
        $inputFilter->setMainServiceLocator($serviceLocator);
        
        $validators = $input->getValidatorChain()->getValidators();
        
        $this->assertEquals(
            $serviceLocator,
            $validators[0]['instance']->getServiceLocator()->getServiceLocator());
    }
}