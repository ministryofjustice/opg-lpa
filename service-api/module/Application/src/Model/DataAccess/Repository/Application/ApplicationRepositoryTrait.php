<?php
namespace Application\Model\DataAccess\Repository\Application;

trait ApplicationRepositoryTrait {

    /**
     * @var ApplicationRepositoryInterface
     */
    private $applicationRepository;

    /**
     * @param ApplicationRepositoryInterface $repo
     */
    public function setApplicationRepository(ApplicationRepositoryInterface $repo)
    {
        $this->applicationRepository = $repo;
    }

}
