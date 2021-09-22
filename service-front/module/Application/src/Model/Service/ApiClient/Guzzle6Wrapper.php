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
class Guzzle6Wrapper extends Guzzle6Adapter implements HttpClientInterface
{
    // Fix method signature so interface is implemented
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return parent::sendRequest($request);
    }
}
