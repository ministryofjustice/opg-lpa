<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use App\Service\ApiClient\Exception\ApiException;
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

    private array $defaultHeaders;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiBaseUri,
        array $defaultHeaders = [],
        private readonly ?Tracer $tracer = null,
    ) {
        $this->defaultHeaders = $defaultHeaders;
    }

    public function updateToken(#[\SensitiveParameter] string $token): void
    {
        $this->defaultHeaders['Token'] = $token;
    }

    public function httpGet(
        string $path,
        array $query = [],
        bool $jsonResponse = true,
        bool $anonymous = false,
        array $additionalHeaders = [],
    ): array|null|string {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            if ($value !== null) {
                $url = Uri::withQueryValue($url, $name, (string) $value);
            }
        }

        $request  = new Request('GET', $url, $this->buildHeaders($additionalHeaders, $anonymous));
        $response = $this->httpClient->sendRequest($request);

        return match ($response->getStatusCode()) {
            200     => $this->handleResponse($response, $jsonResponse),
            204     => null,
            default => $this->handleErrorResponse($response),
        };
    }

    public function httpPost(
        string $path,
        array $payload = [],
        array $additionalHeaders = [],
    ): array|null|string {
        $request  = new Request('POST', $this->apiBaseUri . $path, $this->buildHeaders($additionalHeaders), json_encode($payload));
        $response = $this->httpClient->sendRequest($request);

        return match ($response->getStatusCode()) {
            200, 201 => $this->handleResponse($response),
            204      => null,
            default  => $this->handleErrorResponse($response),
        };
    }

    public function httpPut(string $path, array $payload = []): array|null|string
    {
        $request  = new Request('PUT', new Uri($this->apiBaseUri . $path), $this->buildHeaders(), json_encode($payload));
        $response = $this->httpClient->sendRequest($request);

        return match ($response->getStatusCode()) {
            200, 201 => $this->handleResponse($response),
            204      => null,
            default  => $this->handleErrorResponse($response),
        };
    }

    public function httpPatch(string $path, array $payload = []): array|null|string
    {
        $request  = new Request('PATCH', new Uri($this->apiBaseUri . $path), $this->buildHeaders(), json_encode($payload));
        $response = $this->httpClient->sendRequest($request);

        return match ($response->getStatusCode()) {
            200, 201 => $this->handleResponse($response),
            default  => $this->handleErrorResponse($response),
        };
    }

    public function httpDelete(string $path): null
    {
        $request  = new Request('DELETE', new Uri($this->apiBaseUri . $path), $this->buildHeaders());
        $response = $this->httpClient->sendRequest($request);

        return match ($response->getStatusCode()) {
            204     => null,
            default => $this->handleErrorResponse($response),
        };
    }

    private function buildHeaders(array $additionalHeaders = [], bool $anonymous = false): array
    {
        $headers = [
            'Accept'          => 'application/json, application/problem+json',
            'Accept-Language' => 'en',
            'Content-Type'    => 'application/json; charset=utf-8',
            'User-Agent'      => 'LPA-FRONT',
        ];

        foreach ($this->defaultHeaders as $name => $value) {
            if ($anonymous && $name === 'Token') {
                continue;
            }
            if ($value !== null) {
                $headers[$name] = $value;
            }
        }

        $headers = array_merge($headers, $additionalHeaders);

        if ($this->tracer !== null) {
            $xTraceId = $this->tracer->getTraceHeaderToForward();
            if ($xTraceId !== null) {
                $headers['X-Trace-Id'] = $xTraceId;
            }
        }

        return $headers;
    }

    private function handleResponse(ResponseInterface $response, bool $jsonResponse = true): array|string
    {
        $body = strval($response->getBody());

        if ($jsonResponse) {
            $body = json_decode($body, true);
            if (!is_array($body)) {
                throw new ApiException($response, 'Malformed JSON response from server');
            }
        }

        return $body;
    }

    private function handleErrorResponse(ResponseInterface $response): never
    {
        $exception = new ApiException($response);
        $method    = $response->getStatusCode() >= 500 ? 'error' : 'info';

        $this->getLogger()->$method('API client error response', [
            'error_code' => 'API_CLIENT_ERROR_RESPONSE',
            'status'     => $response->getStatusCode(),
            'exception'  => $exception,
        ]);

        throw $exception;
    }
}
