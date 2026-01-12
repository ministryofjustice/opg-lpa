<?php

namespace Application\Model\Service\ApiClient;

use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Http\Client\HttpClient as HttpClientInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use MakeShared\Telemetry\Tracer;

class ClientFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Client
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var HttpClientInterface $httpClient */
        $httpClient = $container->get('HttpClient');

        $baseApiUri = $container->get('config')['api_client']['api_uri'];

        /** @var Tracer */
        $tracer = $container->get('TelemetryTracer');

        $defaultHeaders = [];

        /** @var SessionUtility $sessionUtility */
        $sessionUtility = $container->get(SessionUtility::class);
        $identity = $sessionUtility->getFromMvc(ContainerNamespace::USER_DETAILS, 'identity');

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
