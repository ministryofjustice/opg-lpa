<?php

namespace Application\Model\Service\ApiClient;

use Http\Adapter\Guzzle6\Client as Guzzle6Adapter;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * The Guzzle 6 adapter does actually implement HttpClientInterface (nearly),
 * but the version of the adapter we have doesn't declare this implementation and
 * the method signature is wrong.
 *
 * This is a wrapper to fix that. It declares that the adapter implements the interface
 * so that the Notify client library will accept instances of
 * the adapter being passed to its constructor. It also fixes the signature
 * for the method to match the one required by HttpClientInterface.
 */
class Guzzle6Wrapper implements HttpClientInterface
{
    /**
     * Wrapped Guzzle 6 adapter instance
     *
     * @var Guzzle6Adapter
     */
    private $adapter;

    /**
     * Constructor
     *
     * @param Guzzle6Adapter $adapter Guzzle 6 adapter to wrap; if set, $config
     * is ignored
     */
    public function __construct(Guzzle6Adapter $adapter = null)
    {
        if (is_null($adapter)) {
            $adapter = new Guzzle6Adapter();
        }
        $this->adapter = $adapter;
    }

    // Fix method signature so interface is implemented
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->adapter->sendRequest($request);
    }
}
