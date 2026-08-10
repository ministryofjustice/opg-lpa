<?php

declare(strict_types=1);

namespace Application\Model\DataAccess\Postgres;

use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

class AbstractBase implements LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * @psalm-suppress PossiblyUnusedMethod Called (via factory-based instantiation) for every
     *     class extending AbstractBase; Psalm cannot trace this dynamic instantiation.
     */
    final public function __construct(
        protected DbWrapper $dbWrapper,
        protected array $config = []
    ) {
    }

    public function config(): array
    {
        return $this->config;
    }
}
