<?php

namespace Application\Model\DataAccess\Repository\Application;

trait WhoRepositoryTrait
{
    /**
     * @var ?WhoRepositoryInterface
     */
    private ?WhoRepositoryInterface $whoRepository;

    /**
     * @param WhoRepositoryInterface $repo
     * @psalm-api
     */
    public function setWhoRepository(WhoRepositoryInterface $repo): void
    {
        $this->whoRepository = $repo;
    }

    /**
     * @return WhoRepositoryInterface
     */
    private function getWhoRepository(): WhoRepositoryInterface
    {
        if (!($this->whoRepository instanceof WhoRepositoryInterface)) {
            throw new \RuntimeException("Instance of WhoRepository not set");
        }

        return $this->whoRepository;
    }
}
