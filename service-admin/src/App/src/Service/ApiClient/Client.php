<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 * @package App\Service\ApiClient
 */
class Client
{
    public function __construct(
        private ClientInterface $httpClient,
        private string $apiBaseUri,
        #[\SensitiveParameter] private string $serviceSecret,
    ) {
    }

    /**
     * Performs a GET against the API
     *
     * @param string $path
     * @param array<string, mixed> $query
     * @return mixed|null
     * @throw RuntimeException | ApiException
     */
    public function httpGet($path, array $query = [])
    {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, urlencode((string) $value));
        }

        $request = new Request('GET', $url, $this->buildHeaders());

        //  Can throw RuntimeException if there is a problem
        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
                return $this->handleResponse($response);
            case 404:
                return null;
            default:
                return $this->handleErrorResponse($response);
        }
    }

    /**
     * Generates the standard set of HTTP headers expected by the API
     *
     * @return array<string, string>
     */
    private function buildHeaders()
    {
        return [
            'Accept'       => 'application/json, application/problem+json',
            'Content-Type' => 'application/json',
            'User-agent'   => 'LPA-ADMIN',
            'X-Shared-Secret'   => $this->serviceSecret,
        ];
    }

    /**
     * Successful response processing
     *
     * @param ResponseInterface $response
     * @return mixed
     * @throw ApiException
     */
    private function handleResponse(ResponseInterface $response)
    {
        $body = json_decode(strval($response->getBody()), true);

        //  If the body isn't an array now then it wasn't JSON before
        if (!is_array($body)) {
            throw new ApiException($response, 'Malformed JSON response from server');
        }

        return $body;
    }

    /**
     * Unsuccessful response processing
     *
     * @param ResponseInterface $response
     * @return null
     * @throw ApiException
     */
    protected function handleErrorResponse(ResponseInterface $response)
    {
        throw new ApiException($response);
    }
}
