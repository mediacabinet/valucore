<?php
namespace Valu\InputFilter\ServiceManager;

use Valu\InputFilter\Configurator\Delegate\ParentInputFilterDetector;
use Valu\InputFilter\Configurator\Delegate\ChildInputFilterDetector;
use Valu\InputFilter\Configurator\Delegate\ConfigurationAggregate;
use Valu\InputFilter\InputFilterRepository;
use Valu\InputFilter\Configurator;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class InputFilterRepositoryFactory
    implements FactoryInterface
{
    
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Configuration');
        $cache = $serviceLocator->get('ValuCache');
        
        $repository = new InputFilterRepository();
        $repository->setCache($cache);
        
        $configurator = $repository->getConfigurator();
        $configurator->setCache($cache);
        
        $configurator->getPlugins()->addPeeringServiceManager($serviceLocator);
        
        // Detect parent input filter as early as possible
        $configurator->addDelegate(new ParentInputFilterDetector(), null, array(), 10000);
        
        // Detect child input filters
        $configurator->addDelegate(new ChildInputFilterDetector(), null, array());
        
        /**
         * Configure input filters
         */
        if(isset($config['input_filter'])){
            $config = $config['input_filter'];
            
            // Configure default input filter class name
            if(isset($config['default_type'])){
                $configurator->setDefaultInputFilterClass($config['default_type']);
            }
            
            // Register configuration aggregate delegate
            if(isset($config['config']) && is_array($config['config'])){
                $delegate = new ConfigurationAggregate(array(
                    'config' => $config['config']        
                ));
                
                $configurator->addDelegate($delegate, null, array(), 1);
            }
            
            // Register custom delegates
            if(isset($config['delegates']) && is_array($config['delegates'])){
                foreach($config['delegates'] as $key => $specs){
                    $delegate = isset($specs['delegate']) ? $specs['delegate'] : $key;
                    $name = isset($specs['name']) ? $specs['name'] : null;
                    $options = isset($specs['options']) ? $specs['options'] : array();
                    $priority = (int) isset($specs['priority']) ? $specs['priority'] : 1000;
                    
                    $configurator->addDelegate($delegate, $name, $options, $priority);
                }
            }
        }
        
        return $repository;
    }
}