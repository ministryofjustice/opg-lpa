<?php

namespace Application\Model\DataAccess\Repository\Application;

use MakeShared\Logging\LoggerTrait;
use RuntimeException;
use MakeShared\DataModel\Lpa\Lpa;

trait ApplicationRepositoryTrait
{
    use LoggerTrait;
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

    /**
     * @return ApplicationRepositoryInterface
     */
    private function getApplicationRepository(): ApplicationRepositoryInterface
    {
        if (!($this->applicationRepository instanceof ApplicationRepositoryInterface)) {
            throw new \RuntimeException("Instance of ApplicationRepository not set");
        }

        return $this->applicationRepository;
    }

    protected function getLpa(int $lpaId): ?Lpa
    {
        $result = $this->getApplicationRepository()->getById($lpaId);

        if (is_null($result)) {
            return null;
        }

        return new Lpa($result);
    }

    protected function updateLpa(Lpa $lpa): bool
    {
        $this->getLogger()->info('Updating LPA', [
            'lpaid' => $lpa->id
        ]);

        // Check LPA is (still) valid.
        if ($lpa->validateForApi()->hasErrors()) {
            throw new RuntimeException('LPA object is invalid');
        }

        $result = $this->getApplicationRepository()->update($lpa);

        $this->getLogger()->info('LPA updated successfully', [
            'lpaid' => $lpa->id,
            'updatedAt' => $lpa->updatedAt,
        ]);

        return $result;
    }
}
