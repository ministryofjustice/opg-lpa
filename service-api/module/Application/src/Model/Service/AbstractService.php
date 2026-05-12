<?php

namespace Application\Model\Service;

use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

abstract class AbstractService implements LoggerAwareInterface
{
    use LoggerTrait;

    protected function log(string $level, string $message, array $context = []): void
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message, $context);
        }
    }
}
