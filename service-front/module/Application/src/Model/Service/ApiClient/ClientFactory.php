<?php

namespace Application\Model\Service\ApiClient;

use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Http\Client\HttpClient as HttpClientInterface;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Container;

class ClientFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Client
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var HttpClientInterface $httpClient */
        $httpClient = $container->get('HttpClient');

        $baseApiUri = $container->get('config')['api_client']['api_uri'];

        $defaultHeaders = [];

        /** @var MakeShared\Telemetry\Tracer */
        $tracer = $container->get('TelemetryTracer');

        /** @var Container $userDetailsSession */
        $userDetailsSession = $container->get('UserDetailsSession');
        $identity = $userDetailsSession->identity;

        if ($identity instanceof UserIdentity) {
            $defaultHeaders['Token'] = $identity->token();
        }

        return new Client(
            $httpClient,
            $baseApiUri,
            $defaultHeaders,
            $tracer,
        );
    }
}
