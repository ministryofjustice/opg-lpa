<?php

namespace Application\ControllerFactory;

use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
use Application\Controller\PingController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Aws\Credentials\CredentialProvider;
use Aws\Sqs\SqsClient;
use Http\Client\HttpClient;

class PingControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return PingController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ZendDbAdapter $database */
        $database = $container->get('ZendDbAdapter');

        /** @var SqsClient $sqsClient */
        $sqs = $container->get('SqsClient');

        $config = $container->get('config');

        if (!isset($config['pdf']['queue']['sqs']['settings']['url'])) {
            throw new \RuntimeException('Missing config: SQS URL');
        }

        if (!isset($config['processing-status']['endpoint'])) {
            throw new \RuntimeException('Missing config: Track my LPA endpoint');
        }

        $awsCredential = CredentialProvider::defaultProvider();

        return new PingController(
            $awsCredential,
            $database,
            $sqs,
            $config['pdf']['queue']['sqs']['settings']['url'],
            $config['processing-status']['endpoint'],
            $container->get(HttpClient::class)
        );
    }
}
