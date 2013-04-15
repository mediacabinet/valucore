<?php
namespace Valu\Test\Service;

use Valu\Service\Plugin\Auth;

use PHPUnit_Framework_TestCase as TestCase;

abstract class AbstractServiceTestCase extends TestCase
{
    /**
     * Static user identity
     * 
     * @var array
     */
    protected static $identities = array(
        'member' => array(
            'id'         => 'b0532c8099953acabc060770eb296f86',
            'account'    => '3a33ebb8749638479a58a6bc2c68262d',
            'roles'      => array('/' => 'member'),
            'groups'     => array('bcdd66f7e96f3690b71846a056662ab9'),
            'email'      => 'member@invalid.email.com'
        ),
        'alt-member' => array(
            'id'         => 'a2532c8099953acabc060770eb296f86',
            'account'    => '99aaccb8749638479a58a6bc2c68262d',
            'roles'      => array('/' => 'member'),
            'groups'     => array('1122aaffe96f3690b71846a056662ab9'),
            'email'      => 'alt-member@invalid.email.com'
        ),
        'admin' => array(
            'id'         => 'aa114c8099953acabc060770eb296f86',
            'account'    => '3a33ebb8749638479a58a6bc2c68262d',
            'roles'      => array('/' => 'member', '/account/3a33ebb8749638479a58a6bc2c68262d/' => 'admin'),
            'groups'     => array('bcdd66f7e96f3690b71846a056662ab9'),
            'email'      => 'admin@invalid.email.com'
        ),
        'alt-admin' => array(
            'id'         => 'aabbcc8099953acabc060770eb296f86',
            'account'    => '99aaccb8749638479a58a6bc2c68262d',
            'roles'      => array('/' => 'member', '/account/99aaccb8749638479a58a6bc2c68262d/' => 'admin'),
            'groups'     => array('1122aaffe96f3690b71846a056662ab9'),
            'email'      => 'alt-admin@invalid.email.com'
        ),
        'superuser'      => array(
            'id'         => 'a1112c8099953acabc060770eb296fff',
            'account'    => 'aa33ebb8749638479a58a6bc2c6826dd',
            'roles'      => array('/' => 'superuser'),
            'groups'     => array('66aa11f7e96f3690b71846a056662ab9'),
            'email'      => 'superuser@invalid.email.com'
        )
    );
    
    /**
     * Default identity setting
     * 
     * @var string
     */
    protected static $defaultIdentity = 'member';
    
    /**
     * Application instance
     * 
     * @var \Zend\Mvc\Application
     */
    protected static $application;
    
    /**
     * Service broker instance
     * 
     * @var \Valu\Service\Broker
     */
    protected static $serviceBroker;
    
    /**
     * Default service name
     * 
     * @var string
     */
    protected static $defaultService;
    
    /**
     * Mongo collection to drop on setup
     * 
     * @var string
     */
    protected static $dropMongoCollection = null;
    
    /**
     * Restore identity before each test
     * 
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        self::restoreIdentity();
    }
    
    public static function setUpBeforeClass()
    {
        if (static::$dropMongoCollection) {
            $mongo = static::initApp()->getServiceManager()->get('MongoDb');
            
            foreach ((array) static::$dropMongoCollection as $coll) {
                $mongo->{$coll}->drop();
            }
        }
    }
    
    /**
     * Initialize application
     * 
     * @return \Zend\Mvc\Application
     */
    protected static function initApp()
    {
        if (!self::$application) {
            $config = static::getAppConfig();
            self::$application = \Zend\Mvc\Application::init($config);
        }
        
        return self::$application;
    }
    
    /**
     * Change identity
     * 
     * @param string $param
     * @param mixed $value
     */
    protected static function changeIdentity($name)
    {
        $staticAuth = static::getServiceLoader()->load('StaticAuth');
        $staticAuth->setOption('identity', static::$identities[$name]);
        
        $auth = new Auth();
        $auth->reset();
    }
    
    /**
     * Update identity specs
     * 
     * @param array $specs
     */
    protected static function updateIdentity($specs)
    {
        $staticAuth = static::getServiceLoader()->load('StaticAuth');
        $identity = $staticAuth->getOption('identity');
        
        $identity = array_merge(
            $identity,
            $specs
        );
        
        $staticAuth->setOption('identity', $identity);
        
        $auth = new Auth();
        $auth->reset();
    }
    
    /**
     * Restore identity to its original state
     */
    protected static function restoreIdentity()
    {
        static::changeIdentity(static::$defaultIdentity);
    }
    
    /**
     * Retrieve service broker instance
     * 
     * @return \Valu\Service\Broker
     */
    protected static function getServiceBroker()
    {
        if (!self::$serviceBroker) {
            $application = static::initApp();
            self::$serviceBroker = $application->getServiceManager()->get('ServiceBroker');
            
            self::$serviceBroker->getLoader()->registerService(
                'StaticAuth',
                'Auth',
                'Valu\Auth\Service\StaticAuth',
                array('identity' => static::$identities[static::$defaultIdentity]),
                10000);
        }
        
        return self::$serviceBroker;
    }
    
    /**
     * Retrieve service loader instance
     * 
     * @return \Valu\Service\Loader
     */
    protected static function getServiceLoader()
    {
        return static::getServiceBroker()->getLoader();
    }
    
    /**
     * Retrieve application config
     * 
     * @return array
     */
    protected static function getAppConfig()
    {
        $config = include APPLICATION_TEST_CONFIG_FILE;
        return $config;
    }
    
    /**
     * Retrieve access to service
     *
     * @param string|null $service
     * @return \Valu\Service\ServiceInterface
     */
    protected static function service($service = null)
    {
        $service = is_null($service) ? static::$defaultService : $service;
        return static::getServiceBroker()->service($service);
    }
}
