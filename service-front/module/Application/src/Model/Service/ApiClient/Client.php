<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient as HttpClientInterface;
use MakeShared\Logging\LoggerTrait;
use MakeShared\Telemetry\Tracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;

class Client implements LoggerAwareInterface
{
    use LoggerTrait;

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $apiBaseUri;

    /** @var array */
    private $defaultHeaders;

    /** @var ?Tracer */
    private $tracer;

    /**
     * Client constructor
     *
     * @param HttpClientInterface $httpClient
     * @param string $apiBaseUri
     * @param array $defaultHeaders Array of name => value pairs; headers which are
     *     set on every request; for authenticated requests, this will
     *     contain 'Token' => <token>
     * @param ?Tracer $tracer
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $apiBaseUri,
        array $defaultHeaders = [],
        ?Tracer $tracer = null,
    ) {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiBaseUri;
        $this->defaultHeaders = $defaultHeaders;
        $this->tracer = $tracer;
    }

    /**
     * This method is required to allow the token value to be updated manually during a single user action where
     * necessary. Currently this is required during the change password and change email address flows, as those action
     * trigger a user authentication which updates the auth token in the backend
     *
     * @param $token
     */
    public function updateToken(#[\SensitiveParameter] $token)
    {
        $this->defaultHeaders['Token'] = $token;
    }

    /**
     * Performs a GET against the API
     *
     * @param $path
     * @param array $query
     * @param bool $jsonResponse
     * @param bool $anonymous
     * @param array $additionalHeaders
     * @return array|null|string
     * @throws \Http\Client\Exception
     */
    public function httpGet(
        $path,
        array $query = [],
        $jsonResponse = true,
        $anonymous = false,
        $additionalHeaders = []
    ) {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, $value);
        }

        $headers = $this->buildHeaders($additionalHeaders, $anonymous);

        $request = new Request('GET', $url, $headers);

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
                return $this->handleResponse($response, $jsonResponse);
            case 204:
                return null;
            case 404:
                return $this->handleErrorResponse($response);
            default:
                return $this->handleErrorResponse($response);
        }

        return null;
    }

    /**
     * Performs a POST against the API
     *
     * @param string $path
     * @param array $payload
     * @param array $additionalHeaders
     * @return array|null|string
     * @throws Exception\ApiException
     * @throws \Http\Client\Exception
     */
    public function httpPost($path, array $payload = [], array $additionalHeaders = [])
    {
        $url = $this->apiBaseUri . $path;

        $headers = $this->buildHeaders($additionalHeaders);

        $request = new Request('POST', $url, $headers, json_encode($payload));

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
                return $this->handleResponse($response);
            case 204:
                return null;
            default:
                return $this->handleErrorResponse($response);
        }
    }

    /**
     * Performs a PUT against the API
     *
     * @param string $path
     * @param array $payload
     * @return array|null|string
     * @throws Exception\ApiException
     * @throws \Http\Client\Exception
     */
    public function httpPut($path, array $payload = [])
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PUT', $url, $this->buildHeaders(), json_encode($payload));

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
                return $this->handleResponse($response);
            case 204:
                return null;
            default:
                return $this->handleErrorResponse($response);
        }
    }

    /**
     * Performs a PATCH against the API
     *
     * @param string $path
     * @param array $payload
     * @return array|null|string
     * @throws Exception\ApiException
     * @throws \Http\Client\Exception
     */
    public function httpPatch($path, array $payload = [])
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PATCH', $url, $this->buildHeaders(), json_encode($payload));

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
     * Performs a DELETE against the API
     *
     * @param string $path
     * @return null
     * @throws Exception\ApiException
     * @throws \Http\Client\Exception
     */
    public function httpDelete($path)
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('DELETE', $url, $this->buildHeaders());

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 204:
                return null;
            default:
                return $this->handleErrorResponse($response);
        }
    }

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @param array $additionalHeaders Extra headers to add to request; note that
     * if any of the keys match what is in the default headers, the value
     * in $additionalHeaders overrides the existing value
     * @param bool $anonymous If true, don't include a "Token" header
     * @return array
     */
    private function buildHeaders($additionalHeaders = [], $anonymous = false)
    {
        $headers = [
            'Accept' => 'application/json, application/problem+json',
            'Accept-Language' => 'en',
            'Content-Type' => 'application/json; charset=utf-8',
            'User-Agent' => 'LPA-FRONT',
        ];

        foreach ($this->defaultHeaders as $name => $value) {
            if ($anonymous && $name == 'Token') {
                continue;
            }

            if (!is_null($value)) {
                $headers[$name] = $value;
            }
        }

        $headers = array_merge($headers, $additionalHeaders);

        // Construct an X-Trace-Id header based on the current state of the tracer.
        // We use the tracer as this is tracking the current segment ID,
        // used to set the Parent flag in any forwarded X-Trace-Id header.
        // Note that this is the header originally
        // sent to the front-app, with the Parent flag added;
        // we are forwarding it in our requests to
        // back-end services, such as api-web (and from there, api-app).
        $xTraceIdHeader = null;
        if (!is_null($this->tracer)) {
            $xTraceIdHeader = $this->tracer->getTraceHeaderToForward();
        }

        if (!is_null($xTraceIdHeader)) {
            $headers['X-Trace-Id'] = $xTraceIdHeader;
        }

        return $headers;
    }

    /**
     * Successful response processing
     *
     * @param ResponseInterface $response
     * @param bool $jsonResponse
     * @return array|string
     * @throws Exception\ApiException
     */
    private function handleResponse(ResponseInterface $response, $jsonResponse = true)
    {
        $body = strval($response->getBody());

        if ($jsonResponse == true) {
            $body = json_decode($body, true);

            //  If the body isn't an array now then it wasn't JSON before
            if (!is_array($body)) {
                throw new Exception\ApiException($response, 'Malformed JSON response from server');
            }
        }

        return $body;
    }

    /**
     * Unsuccessful response processing
     *
     * @param ResponseInterface $response
     * @throws Exception\ApiException
     */
    private function handleErrorResponse(ResponseInterface $response)
    {
        $exception = new Exception\ApiException($response);
        $method = $response->getStatusCode() >= 500 ? 'error' : 'info';

        $this->getLogger()->$method('API client error response', [
            'error_code' => 'API_CLIENT_ERROR_RESPONSE',
            'status' => $response->getStatusCode(),
            'exception' => $exception,
        ]);

        throw $exception;
    }
}
