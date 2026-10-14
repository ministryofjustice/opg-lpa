<?php

declare(strict_types=1);

namespace Application\Handler;

use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\Response\JsonResponse;
use MakeShared\Constants;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

readonly class HealthCheckDependenciesHandler implements RequestHandlerInterface
{
    /**
     * @param array{
     *   onelogin: array{discovery_url: string, user_agent: string},
     *   processing-status: array{endpoint: string}
     * } $config
     */
    public function __construct(
        private ClientInterface $httpClient,
        private CredentialsInterface $awsCredentials,
        private SignatureV4 $signer,
        private LoggerInterface $logger,
        private array $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = [
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
            'oneLogin' => $this->checkOneLogin(),
            'opg-data-lpa' => $this->checkOpgDataLpa(),
        ];

        if ($result['oneLogin']['ok']) {
            $result['ok'] = true;
            $result['status'] = Constants::STATUS_PASS;
        }

        return new JsonResponse($result);
    }

    /**
     * @return array{ok: bool, status: string, details?: mixed}
     */
    private function checkOneLogin(): array
    {
        try {
            $url = new Uri($this->config['onelogin']['discovery_url']);

            $request = new Request('GET', $url, [
                'User-Agent' => $this->config['onelogin']['user_agent'],
            ]);

            $response = $this->httpClient->sendRequest($request);

            $ok = $response->getStatusCode() === 200;
            return [
                'ok' => $ok,
                'status' => $ok ? Constants::STATUS_PASS : Constants::STATUS_FAIL,
                'details' => [
                    'response_code' => $response->getStatusCode(),
                ],
            ];
        } catch (Exception $e) {
            $this->logger->error(
                "One Login not available to API at $url: " . $e->getMessage()
            );
            return ['ok' => false, 'status' => Constants::STATUS_FAIL];
        }
    }

    /**
     * @return array{ok: bool, status: string, details?: mixed}
     */
    private function checkOpgDataLpa(): array
    {
        try {
            $url = new Uri(rtrim($this->config['processing-status']['endpoint'], '/') . '/healthcheck');

            $request = new Request('GET', $url, [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ]);

            // Sign the request with an AWS Authorization header.
            $signedRequest = $this->signer->signRequest($request, $this->awsCredentials);

            $response = $this->httpClient->sendRequest($signedRequest);

            $ok = $response->getStatusCode() === 200;
            return [
                'ok' => $ok,
                'status' => $ok ? Constants::STATUS_PASS : Constants::STATUS_FAIL,
                'details' => [
                    'response_code' => $response->getStatusCode(),
                ],
            ];
        } catch (Exception $e) {
            $this->logger->error(
                "Sirius gateway not available to API at $url: " . $e->getMessage()
            );
            return ['ok' => false, 'status' => Constants::STATUS_FAIL];
        }
    }
}
