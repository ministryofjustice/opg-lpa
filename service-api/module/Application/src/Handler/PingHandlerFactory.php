<?php

declare(strict_types=1);

namespace Application\Handler;

use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Aws\Sqs\SqsClient;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PingHandlerFactory
{
    public function __invoke(ContainerInterface $container): PingHandler
    {
        /** @var CredentialsInterface $awsCredentials */
        $awsCredentials = $container->get('AwsCredentials');

        /** @var SignatureV4 $awsSigner */
        $awsSigner = $container->get('AwsApiGatewaySignature');

        /** @var ZendDbAdapter $database */
        $database = $container->get('ZendDbAdapter');

        /** @var SqsClient $sqsClient */
        $sqs = $container->get('SqsClient');

        $config = $container->get('config');

        if (!isset($config['pdf']['queue']['sqs']['settings']['url'])) {
            throw new RuntimeException('Missing config: SQS URL');
        }

        if (!isset($config['processing-status']['endpoint'])) {
            throw new RuntimeException('Missing config: Track my LPA endpoint');
        }

        return new PingHandler(
            $awsCredentials,
            $awsSigner,
            $database,
            $sqs,
            $config['pdf']['queue']['sqs']['settings']['url'],
            $config['processing-status']['endpoint'],
            $container->get(ClientInterface::class),
            $container->get(LoggerInterface::class),
        );
    }
}
