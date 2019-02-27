<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient as HttpClientInterface;
use Opg\Lpa\Logger\LoggerTrait;
use Psr\Http\Message\ResponseInterface;

class Client
{

    use LoggerTrait;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiBaseUri;

    /**
     * @var string
     */
    private $token;

    /**
     * Client constructor
     *
     * @param HttpClientInterface $httpClient
     * @param $apiBaseUri
     * @param $token
     */
    public function __construct(HttpClientInterface $httpClient, $apiBaseUri, $token)
    {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiBaseUri;
        $this->token = $token;
    }

    /**
     * This method is required to allow the token value to be updated manually during a single user action where necessary
     * Currently this is required during the change password and change email address flows, as those action trigger a
     * user authentication which updates the auth token in the backend
     *
     * @param $token
     */
    public function updateToken($token)
    {
        $this->token = $token;
    }

    /**
     * Performs a GET against the API
     *
     * @param $path
     * @param array $query
     * @param bool $jsonResponse
     * @param bool $anonymous
     * @param array|null $additionalHeaders
     * @return array|null
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

        $headers = $this->buildHeaders();

        if ($anonymous) {
            unset($headers['Token']);
        }

        if ($additionalHeaders) {
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
    }

    /**
     * Performs a POST against the API
     *
     * @param string $path
     * @param array  $payload
     * @return array|null
     * @throws Exception\ApiException
     */
    public function httpPost($path, array $payload = [])
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('POST', $url, $this->buildHeaders(), json_encode($payload));

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
     * @param array  $payload
     * @return array
     * @throws Exception\ApiException
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
     * @param array  $payload
     * @return array
     * @throws Exception\ApiException
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
     * Successful response processing
     *
     * @param ResponseInterface $response
     * @param bool $jsonResponse
     * @return array
     * @throws Exception\ApiException
     */
    private function handleResponse(ResponseInterface $response, $jsonResponse = true)
    {
        $body = $response->getBody();

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
     * @return null
     * @throws Exception\ApiException
     */
    private function handleErrorResponse(ResponseInterface $response)
    {
        $this->getLogger()->info($response->getBody());
        throw new Exception\ApiException($response);
    }
}
