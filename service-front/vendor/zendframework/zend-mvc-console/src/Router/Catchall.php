<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console\Router;

use Traversable;
use Zend\Console\Request as ConsoleRequest;
use Zend\Stdlib\RequestInterface as Request;

class Catchall implements RouteInterface
{
    /**
     * Parts of the route.
     *
     * @var array
     */
    protected $parts;

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Parameter name aliases.
     *
     * @var array
     */
    protected $aliases;

    /**
     * List of assembled parameters.
     *
     * @var array
     */
    protected $assembledParams = [];

    /**
     * Create a new simple console route.
     *
     * @param  array $defaults
     * @return Catchall
     */
    public function __construct(array $defaults = [])
    {
        $this->defaults = $defaults;
    }

    /**
     * factory(): defined by Route interface.
     *
     * @see    \Zend\Mvc\Router\RouteInterface::factory()
     * @param  array|Traversable $options
     * @return Simple
     */
    public static function factory($options = [])
    {
        return new static(isset($options['defaults']) ? $options['defaults'] : []);
    }

    /**
     * match(): defined by Route interface.
     *
     * @see     \Zend\Mvc\Router\RouteInterface::match()
     * @param   Request $request
     * @return  RouteMatch
     */
    public function match(Request $request)
    {
        if (! $request instanceof ConsoleRequest) {
            return;
        }

        return new RouteMatch($this->defaults);
    }

    /**
     * assemble(): Defined by Route interface.
     *
     * @see    \Zend\Mvc\Router\RouteInterface::assemble()
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = [], array $options = [])
    {
        $this->assembledParams = [];
    }

    /**
     * getAssembledParams(): defined by Route interface.
     *
     * @see    RouteInterface::getAssembledParams
     * @return array
     */
    public function getAssembledParams()
    {
        return $this->assembledParams;
    }
}
