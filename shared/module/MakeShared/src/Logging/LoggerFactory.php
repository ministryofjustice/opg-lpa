<?php

namespace MakeShared\Logging;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

class LoggerFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null) : Logger
    {
        $logger = new Logger('MakeAnLPALogger');
        $logger->pushHandler(new StreamHandler('php://stderr', Level::Debug  ));
        $logger->pushProcessor($container->get(MvcEventProcessor::class))
            ->pushProcessor($container->get(HeadersProcessor::class)
            ->pushProcessor($container->get(TraceIdProcessor::class);

        return $logger;
    }
}