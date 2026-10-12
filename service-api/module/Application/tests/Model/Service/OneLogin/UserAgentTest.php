<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\AuthorisationClientManager;
use Application\Model\Service\OneLogin\FacileAuthorizationServiceAdapter;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Adapter\Guzzle7\Client as GuzzlePsr18;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

/**
 * GOV.UK One Login rejects direct calls that arrive without a User-Agent with a 403, and asks for
 * the same value on every one of them.
 *
 * @see https://docs.sign-in.service.gov.uk/before-integrating/set-user-agent-header/
 */
class UserAgentTest extends TestCase
{
    private const CONFIGURED_USER_AGENT = 'opg-lpa/test-tag (https://www.lastingpowerofattorney.service.gov.uk)';

    /**
     * @return array<string, array{class-string}>
     */
    public static function factoryProvider(): array
    {
        return [
            'authorisation client manager' => [AuthorisationClientManager::class],
            'authorization service adapter' => [FacileAuthorizationServiceAdapter::class],
        ];
    }

    /**
     * @dataProvider factoryProvider
     * @param class-string $service
     */
    public function testFactoryConfiguresTheOneLoginUserAgent(string $service): void
    {
        $guzzle = $this->guzzleClientBuiltBy($service);

        $this->assertSame(
            ['User-Agent' => self::CONFIGURED_USER_AGENT],
            $guzzle->getConfig('headers'),
        );
    }

    public function testFactoriesAgreeOnTheValueSent(): void
    {
        $agents = [];

        foreach (array_keys(self::factoryProvider()) as $name) {
            $service = self::factoryProvider()[$name][0];
            $agents[] = $this->guzzleClientBuiltBy($service)->getConfig('headers')['User-Agent'];
        }

        $this->assertCount(1, array_unique($agents), 'every direct call must send the same value');
    }

    public function testConfiguredValueIsSentOnEveryRequest(): void
    {
        $history = [];
        $stack   = HandlerStack::create(new MockHandler(array_fill(0, 3, new Response(200, [], '{}'))));
        $stack->push(Middleware::history($history));

        $client = new GuzzlePsr18(new GuzzleClient([
            'handler' => $stack,
            'headers' => ['User-Agent' => self::CONFIGURED_USER_AGENT],
        ]));

        $client->sendRequest(new Request('GET', 'https://oidc.example.com/.well-known/openid-configuration'));
        $client->sendRequest(new Request('GET', 'https://oidc.example.com/.well-known/jwks.json'));
        $client->sendRequest(new Request('POST', 'https://oidc.example.com/token'));

        $this->assertCount(3, $history);

        foreach ($history as $transaction) {
            $this->assertSame(
                self::CONFIGURED_USER_AGENT,
                $transaction['request']->getHeaderLine('User-Agent'),
            );
        }
    }

    public function testShippedConfigMatchesTheDocumentedFormat(): void
    {
        $config = include __DIR__ . '/../../../../../../config/autoload/global.php';

        $this->assertMatchesRegularExpression(
            '#^opg-lpa/\S+ \(https://www\.lastingpowerofattorney\.service\.gov\.uk\)$#',
            $config['onelogin']['user_agent'],
        );
    }

    private function guzzleClientBuiltBy(string $service): GuzzleClient
    {
        $built = $this->invokeFactory($service, [
            'onelogin' => [
                'client_id'     => 'test-client-id',
                'discovery_url' => 'https://oidc.example.com/.well-known/openid-configuration',
                'private_key'   => KeyPairManagerTest::rsaPrivateKey(),
                'key_id'        => 'test-kid',
                'user_agent'    => self::CONFIGURED_USER_AGENT,
            ],
        ]);

        return $this->unwrapGuzzle($built);
    }

    private function invokeFactory(string $service, array $config): object
    {
        $moduleConfig = include __DIR__ . '/../../../../config/module.config.php';

        $container = new ServiceManager($moduleConfig['service_manager']);
        $container->setAllowOverride(true);
        $container->setService('config', $config);

        return $container->get($service);
    }

    private function unwrapGuzzle(object $built): GuzzleClient
    {
        $psr18 = $built instanceof GuzzlePsr18 ? $built : $this->readProperty($built, GuzzlePsr18::class);

        $guzzle = $this->readProperty($psr18, GuzzleClient::class);

        $this->assertInstanceOf(GuzzleClient::class, $guzzle);

        return $guzzle;
    }

    private function readProperty(object $subject, string $ofType): object
    {
        foreach ((new \ReflectionObject($subject))->getProperties() as $property) {
            $value = $property->isInitialized($subject) ? $property->getValue($subject) : null;

            if ($value instanceof $ofType) {
                return $value;
            }

            if (is_object($value)) {
                foreach ((new \ReflectionObject($value))->getProperties() as $nested) {
                    $candidate = $nested->isInitialized($value) ? $nested->getValue($value) : null;

                    if ($candidate instanceof $ofType) {
                        return $candidate;
                    }
                }
            }
        }

        $this->fail(sprintf('Could not reach a %s on %s', $ofType, $subject::class));
    }
}
