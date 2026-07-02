<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Authentication\AuthenticationService;
use App\Model\Service\Authentication\Identity\User as Identity;
use Monolog\Logger;
use Monolog\LogRecord;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class UserIdMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $this->authenticationService->getIdentity();

        if ($identity instanceof Identity && $this->logger instanceof Logger) {
            $userId = $identity->id();

            if (!empty($this->logger->getProcessors())) {
                $this->logger->popProcessor();
            }

            $this->logger->pushProcessor(function (LogRecord $record) use ($userId): LogRecord {
                return $record->with(extra: array_merge($record->extra, ['user_id' => $userId]));
            });
        }

        return $handler->handle($request);
    }
}
