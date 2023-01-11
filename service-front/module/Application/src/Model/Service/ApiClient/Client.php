<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient as HttpClientInterface;
use MakeShared\Logging\LoggerTrait;
use Psr\Http\Message\ResponseInterface;

class Client
{
    use LoggerTrait;

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $apiBaseUri;

    /** @var array */
    private $defaultHeaders;

    /**
     * Client constructor
     *
     * @param HttpClientInterface $httpClient
     * @param $apiBaseUri
     * @param $defaultHeaders Array of name => value pairs; headers which are
     *     set on every request; usually this will consist of
     *     'X-Trace-Id'Token => <trace ID> as a minimum; for authenticated
     *     requests, will also contain 'Token' => <token>
     */
    public function __construct(HttpClientInterface $httpClient, $apiBaseUri, $defaultHeaders = [])
    {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiBaseUri;
        $this->defaultHeaders = $defaultHeaders;
    }

    /**
     * This method is required to allow the token value to be updated manually during a single user action where
     * necessary. Currently this is required during the change password and change email address flows, as those action
     * trigger a user authentication which updates the auth token in the backend
     *
     * @param string $token
     */
    public function updateToken(#[\SensitiveParameter] ?string $token)
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
     * @param array|null $additionalHeaders
     * @return array|null|string
     * @throws \Http\Client\Exception
     */
    public function httpGet(
        $path,
        array $query = [],
        $jsonResponse = true,
        $anonymous = false,
        $additionalHeaders = null
    ) {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, $value);
        }

        $headers = $this->buildHeaders($anonymous);

        if (is_array($additionalHeaders)) {
            $headers += $additionalHeaders;
        }

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
     * @param array $additionalHeaders - extra headers to add to request
     * @return array|null|string
     * @throws Exception\ApiException
     * @throws \Http\Client\Exception
     */
    public function httpPost($path, array $payload = [], array $additionalHeaders = [])
    {
        $url = $this->apiBaseUri . $path;

        $headers = $this->buildHeaders() + $additionalHeaders;

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
     * @param bool $anonymous If true, don't include a "Token" header
     * @return array
     */
    private function buildHeaders($anonymous = false)
    {
        $headers = [
            'Accept' => 'application/json',
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
        $body = '' . $response->getBody();

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
        $this->getLogger()->err($exception->getMessage(), ['headers' => $this->defaultHeaders]);
        throw $exception;
    }
}
