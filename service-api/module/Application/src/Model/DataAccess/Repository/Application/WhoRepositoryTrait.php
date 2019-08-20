<?php
namespace Application\Model\DataAccess\Repository\Application;

trait WhoRepositoryTrait {

    /**
     * @var WhoRepositoryInterface
     */
    private $whoRepository;

    /**
     * @param WhoRepositoryInterface $repo
     */
    public function setWhoRepository(WhoRepositoryInterface $repo)
    {
        $this->whoRepository = $repo;
    }

    /**
     * @return WhoRepositoryInterface
     */
    private function getWhoRepository() : WhoRepositoryInterface
    {
        if (!($this->whoRepository instanceof WhoRepositoryInterface)) {
            throw new \RuntimeException("Instance of WhoRepository not set");
        }

        return $this->whoRepository;
    }
}
