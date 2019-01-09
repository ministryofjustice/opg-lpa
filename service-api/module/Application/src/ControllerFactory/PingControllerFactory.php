<?php

namespace Application\ControllerFactory;

use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Application\Controller\PingController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Aws\Sqs\SqsClient;

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

        return new PingController(
            $database,
            $sqs,
            $config['pdf']['queue']['sqs']['settings']['url']
        );
    }
}
