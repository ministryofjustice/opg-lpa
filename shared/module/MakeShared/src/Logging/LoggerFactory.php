<?php

namespace MakeShared\Logging;

use Laminas\Http\Request as HttpRequest;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

class LoggerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Logger
    {
        $loggerConfig = $container->get('config')['logging'] ?? [];

        $loggerChannel = $loggerConfig['serviceName'] ?? 'MakeAnLPALogger';
        $loggerMinLevel = $loggerConfig['minLevel'] ?? Level::Debug;

        $logger = new Logger($loggerChannel);
        $formatter = new OpgJsonFormatter();

        $request = $container->get('Request');
        if ($request instanceof HttpRequest) {
            $formatter->requestPath = $request->getUri()->getPath();
            $formatter->requestMethod = $request->getMethod();
        }

        $streamHandler = new StreamHandler('php://stderr', $loggerMinLevel);
        $streamHandler->setFormatter($formatter);

        $logger->pushHandler($streamHandler);
        $logger->pushProcessor($container->get(HeadersProcessor::class))
            ->pushProcessor($container->get(TraceIdProcessor::class));

        return $logger;
    }
}
