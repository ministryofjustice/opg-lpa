<?php

declare(strict_types=1);

namespace Application\Model\DataAccess\Postgres;

use MakeShared\Logging\LoggerTrait;
use Application\Model\DataAccess\Postgres\DbWrapper;
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

    /**
     * @psalm-suppress PossiblyUnusedMethod Called via SharedSpaceRepositoryInterface; inherited
     *     (not overridden) by SharedSpaceData, so Psalm can't trace calls back to this method.
     */
    public function beginTransaction(): void
    {
        $this->dbWrapper->beginTransaction();
    }


    /**
     * @psalm-suppress PossiblyUnusedMethod Called via SharedSpaceRepositoryInterface; inherited
     *     (not overridden) by SharedSpaceData, so Psalm can't trace calls back to this method.
     */
    public function commit(): void
    {
        $this->dbWrapper->commit();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod Called via SharedSpaceRepositoryInterface; inherited
     *     (not overridden) by SharedSpaceData, so Psalm can't trace calls back to this method.
     */
    public function rollback(): void
    {
        $this->dbWrapper->rollback();
    }
}
