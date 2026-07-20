<?php

namespace Application\Model\Service\OneLogin;

use Application\Model\Service\AbstractService;
use GuzzleHttp\Client;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class Service extends AbstractService
{
    use LoggerTrait;

    private array $config = [];
    private ?Client $client = null;
    /** @var callable(int): string */
    private $randomBytes;
    private ?DiscoveryDocumentFetcher $discoveryDocumentFetcher = null;

    public function __construct()
    {
        $this->randomBytes = random_bytes(...);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Optional seam for tests: override the random-byte generator.
     *
     * @param callable(int): string $generator
     */
    public function setRandomByteGenerator(callable $generator): void
    {
        $this->randomBytes = $generator;
    }

    /**
     * Optional seam for tests: override the discovery-document fetcher.
     */
    public function setDiscoveryDocumentFetcher(DiscoveryDocumentFetcher $fetcher): void
    {
        $this->discoveryDocumentFetcher = $fetcher;
    }

    /**
     * Build and return an OIDC authorisation request.
     *
     * @return array{state: string, nonce: string, url: string}
     * @throws RuntimeException
     */
    public function createAuthenticationRequest(string $redirectUrl): array
    {
        $clientId     = $this->config['onelogin']['client_id'] ?? null;
        $discoveryUrl = $this->config['onelogin']['discovery_url'] ?? null;

        if (empty($clientId)) {
            throw new RuntimeException('Missing required config: onelogin.client_id');
        }

        if (empty($discoveryUrl)) {
            throw new RuntimeException('Missing required config: onelogin.discovery_url');
        }

        $generator = $this->randomBytes;

        $state = rtrim(strtr(base64_encode($generator(12)), '+/', '-_'), '=');

        $nonce = hash('sha256', $generator(24));

        $fetcher = $this->discoveryDocumentFetcher
            ?? new DiscoveryDocumentFetcher($this->client, $discoveryUrl);

        $authorizationEndpoint = $fetcher->authorizationEndpoint();

        $url = $authorizationEndpoint . '?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUrl,
            'scope'         => 'openid email',
            'state'         => $state,
            'nonce'         => $nonce,
            'vtr'           => '["Cl.Cm"]',
        ]);

        $this->getLogger()->info('auth.onelogin.request_created', [
            'redirect_host' => parse_url($redirectUrl, PHP_URL_HOST),
        ]);

        return ['state' => $state, 'nonce' => $nonce, 'url' => $url];
    }
}
