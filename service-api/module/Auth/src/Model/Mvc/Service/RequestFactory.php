<?php
namespace Auth\Model\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\PhpEnvironment\Request as HttpRequest;

use Auth\Model\Http\PhpEnvironment\JsonRequest as HttpJsonRequest;

/**
 * Extension to the standard RequestFactory to allow as HttpJsonRequest to be returned
 * instead of the standard HttpRequest.
 *
 * Class RequestFactory
 * @package Auth\Model\Mvc\Service
 */
class RequestFactory extends \Zend\Mvc\Service\RequestFactory
{
    /**
     * Create and return a request instance, according to current environment.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return ConsoleRequest|HttpRequest
     */
        public function __invoke(ContainerInterface $container, $name, array $options = null)
    {

        $request = parent::__invoke( $container, $name, $options );

        // If it's a HttpRequest, replace it with a HttpJsonRequest...
        if( $request instanceof HttpRequest && !$request instanceof HttpJsonRequest ){

            $request = new HttpJsonRequest();

        }

        return $request;

    }

}
