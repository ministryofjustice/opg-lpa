<?php

namespace App\Service\ApiClient;

use App\Handler\Traits\JwtTrait;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 * @package App\Service\ApiClient
 */
class Client
{
    use JwtTrait;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiBaseUri;

    /**
     * Client constructor
     *
     * @param HttpClient $httpClient
     * @param string $apiBaseUri
     */
    public function __construct(HttpClient $httpClient, string $apiBaseUri)
    {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiBaseUri;
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
            $url = Uri::withQueryValue($url, $name, urlencode($value));
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
     * Performs a POST against the API
     *
     * @param string $path
     * @param mixed $payload
     * @return mixed|null
     * @throw RuntimeException | ApiException
     */
    public function httpPost($path, $payload = [])
    {
        $url = new Uri($this->apiBaseUri . $path);

        $encodedPayload = json_encode($payload);

        if (!$encodedPayload) {
            // JSON parse error
            throw new \RuntimeException('Invalid JSON payload supplied as POST body');
        }

        $request = new Request('POST', $url, $this->buildHeaders(), $encodedPayload);

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
                return $this->handleResponse($response);
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
        $headerLines = [
            'Accept'        => 'application/json, application/problem+json',
            'Content-Type'  => 'application/json',
            'User-agent'    => 'LPA-ADMIN'
        ];

        $apiToken = $this->getTokenData('token')->toString();

        //  If the logged in user has an auth token already then set that in the header
        if (!is_null($apiToken)) {
            $headerLines['token'] = $apiToken;
        }

        return $headerLines;
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
        $body = json_decode('' . $response->getBody(), true);

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
