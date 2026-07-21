<?php

namespace Application\Model\Service\OneLogin;

use Application\Model\Service\AbstractService;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class Service extends AbstractService
{
    use LoggerTrait;

    private array $config = [];
    private ?DiscoveryDocumentFetcher $discoveryDocumentFetcher = null;
    /** @var callable(positive-int): string */
    private $randomBytes;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
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
    public function setDiscoveryDocumentFetcher(DiscoveryDocumentFetcher $fetcher): void
    {
        $this->discoveryDocumentFetcher = $fetcher;
    }

    /**
     * Optional seam for tests: override the random-byte generator.
     *
     * @param callable(positive-int): string $generator
     */
    public function setRandomByteGenerator(callable $generator): void
    {
        $this->randomBytes = $generator;
    }

    /**
     * Build and return an OIDC authorisation request.
     *
     * @return array{state: string, nonce: string, url: string}
     * @throws RuntimeException
     */
    public function createAuthenticationRequest(string $redirectUrl): array
    {
        $clientId = $this->config['onelogin']['client_id'] ?? null;

        if (!is_string($clientId) || $clientId === '') {
            throw new RuntimeException('Missing required config: onelogin.client_id');
        }

        $generator = $this->randomBytes;

        $state = bin2hex($generator(12));

        $nonce = bin2hex($generator(16));

        if ($this->discoveryDocumentFetcher === null) {
            throw new RuntimeException('DiscoveryDocumentFetcher must be set via setDiscoveryDocumentFetcher()');
        }

        $authorizationEndpoint = $this->discoveryDocumentFetcher->authorizationEndpoint();

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
