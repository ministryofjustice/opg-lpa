<?php

namespace Application\Model\Service\AuthClient;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient as HttpClientInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $authBaseUri;

    /**
     * @var string
     */
    private $token;

    /**
     * Client constructor
     *
     * @param HttpClientInterface $httpClient
     * @param $authBaseUri
     * @param $token
     */
    public function __construct(HttpClientInterface $httpClient, $authBaseUri, $token)
    {
        $this->httpClient = $httpClient;
        $this->authBaseUri = $authBaseUri;
        $this->token = $token;
    }

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders()
    {
        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'LPA-FRONT'
        ];

        if (!is_null($this->token)) {
            $headers['Token'] = $this->token;
        }

        return $headers;
    }

    /**
     * Performs a GET against the API
     *
     * @param $path
     * @param array $query
     * @return ResponseInterface
     */
    public function httpGet($path, array $query = [])
    {
        $url = new Uri($this->authBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, urlencode($value));
        }

        $request = new Request('GET', $url, $this->buildHeaders(), '{}');

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 404])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    /**
     * Performs a POST against the API
     *
     * @param $path
     * @param array $payload
     * @return ResponseInterface
     */
    public function httpPost($path, array $payload = [])
    {
        $url = new Uri($this->authBaseUri . $path);

        $body = (!empty($payload) ? json_encode($payload) : null);
        $request = new Request('POST', $url, $this->buildHeaders(), $body);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 201, 204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    /**
     * Performs a DELETE against the API
     *
     * @param $path
     * @return ResponseInterface
     */
    public function httpDelete($path)
    {
        $url = new Uri($this->authBaseUri . $path);

        $request = new Request('DELETE', $url, $this->buildHeaders(), '{}');

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    /**
     * Called with a response from the API when the response code was unsuccessful. i.e. not 20X.
     *
     * @param ResponseInterface $response
     *
     * @return Exception\ResponseException
     */
    protected function createErrorException(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        $message = "HTTP:{$response->getStatusCode()} - ";
        $message .= (is_array($body)) ? print_r($body, true) : 'Unexpected response from server';

        return new Exception\ResponseException($message, $response->getStatusCode(), $response);
    }
}
