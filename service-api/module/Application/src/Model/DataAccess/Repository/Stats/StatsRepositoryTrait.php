<?php
namespace Application\Model\DataAccess\Repository\Stats;

trait StatsRepositoryTrait {

    /**
     * @var StatsRepositoryInterface
     */
    private $statsRepository;

    /**
     * @param StatsRepositoryInterface $repo
     */
    public function setStatsRepository(StatsRepositoryInterface $repo)
    {
        $this->statsRepository = $repo;
    }

    /**
     * @return StatsRepositoryInterface
     */
    private function getStatsRepository() : StatsRepositoryInterface
    {
        if (!($this->statsRepository instanceof StatsRepositoryInterface)) {
            throw new \RuntimeException("Instance of StatsRepository not set");
        }

        return $this->statsRepository;
    }

}
