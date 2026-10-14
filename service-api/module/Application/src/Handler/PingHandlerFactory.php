<?php

declare(strict_types=1);

namespace Application\Handler;

use Aws\Sqs\SqsClient;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PingHandlerFactory
{
    public function __invoke(ContainerInterface $container): PingHandler
    {
        /** @var ZendDbAdapter $database */
        $database = $container->get('ZendDbAdapter');

        /** @var SqsClient $sqsClient */
        $sqs = $container->get('SqsClient');

        $config = $container->get('config');

        if (!isset($config['pdf']['queue']['sqs']['settings']['url'])) {
            throw new RuntimeException('Missing config: SQS URL');
        }

        return new PingHandler(
            $database,
            $sqs,
            $config['pdf']['queue']['sqs']['settings']['url'],
            $container->get(LoggerInterface::class),
        );
    }
}
