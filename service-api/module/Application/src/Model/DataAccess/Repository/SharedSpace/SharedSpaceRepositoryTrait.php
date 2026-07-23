<?php

declare(strict_types=1);

namespace Application\Model\DataAccess\Repository\SharedSpace;

/** @psalm-require-implements \Psr\Log\LoggerAwareInterface */
trait SharedSpaceRepositoryTrait
{
    /**
     * @var SharedSpaceRepositoryInterface
     */
    private $sharedSpaceRepository;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setSharedSpaceRepository(SharedSpaceRepositoryInterface $repo): void
    {
        $this->sharedSpaceRepository = $repo;
    }

    private function getSharedSpaceRepository(): SharedSpaceRepositoryInterface
    {
        if (!($this->sharedSpaceRepository instanceof SharedSpaceRepositoryInterface)) {
            throw new \RuntimeException('Instance of SharedSpaceRepository not set');
        }

        return $this->sharedSpaceRepository;
    }
}
