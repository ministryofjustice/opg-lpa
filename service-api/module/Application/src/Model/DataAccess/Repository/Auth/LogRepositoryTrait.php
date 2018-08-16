<?php
namespace Application\Model\DataAccess\Repository\Auth;

trait LogRepositoryTrait
{

    /**
     * @var LogRepositoryInterface
     */
    private $logRepository;

    /**
     * @param LogRepositoryInterface $repo
     */
    public function setLogRepository(LogRepositoryInterface $repo)
    {
        $this->logRepository = $repo;
    }

    /**
     * @return LogRepositoryInterface
     */
    private function getLogRepository() : LogRepositoryInterface
    {
        if (!($this->logRepository instanceof LogRepositoryInterface)) {
            throw new \RuntimeException("Instance of LogRepository not set");
        }

        return $this->logRepository;
    }

}
