<?php
namespace Valu\Router\Http;

use Zend\Mvc\Router\Http\RouteInterface,
    Zend\Mvc\Router\Http\RouteMatch,
    Zend\Stdlib\RequestInterface as Request;

/**
 * Dummy route that matches with any request
 */
class Dummy implements RouteInterface
{
    
    /**
     * Array of default values
     * 
     * @var array
     */
    protected $defaults = array();
    
    /**
     * Create a new route that matches any request
     *
     * @param  array  $defaults
     */
    public function __construct(array $defaults = array())
    {
        $this->defaults = $defaults;
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    Route::factory()
     * @param  array|\Traversable $options
     * @throws \Zend\Mvc\Router\Exception\InvalidArgumentException
     * @return Hostname
     */
    public static function factory($options = array())
    {

        if (!isset($options['defaults'])) {
            $options['defaults'] = array();
        }

        return new static($options['defaults']);
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    Route::match()
     * @param  Request $request
     * @return RouteMatch
     */
    public function match(Request $request)
    {
        return new RouteMatch($this->defaults);
    }

    /**
     * assemble(): Defined by RouteInterface interface.
     *
     * @see    Route::assemble()
     * @param  array $params
     * @param  array $options
     * @return mixed
     * @throws Exception\InvalidArgumentException
     */
    public function assemble(array $params = array(), array $options = array())
    {
        return '';
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    Route::getAssembledParams
     * @return array
     */
    public function getAssembledParams()
    {
        return array();
    }
}
