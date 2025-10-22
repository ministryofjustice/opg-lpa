<?php

namespace Application\Model\DataAccess\Repository\Application;

use Psr\Log\LoggerAwareInterface;
use RuntimeException;
use MakeShared\DataModel\Lpa\Lpa;

/** @psalm-require-implements LoggerAwareInterface */
trait ApplicationRepositoryTrait
{
    /**
     * @var ApplicationRepositoryInterface
     */
    private $applicationRepository;

    /**
     * @param ApplicationRepositoryInterface $repo
     * @psalm-api
     */
    public function setApplicationRepository(ApplicationRepositoryInterface $repo): void
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

    protected function getLpa(string $lpaId): ?Lpa
    {
        $result = $this->getApplicationRepository()->getById(intval($lpaId));

        if (is_null($result)) {
            return null;
        }

        return new Lpa($result);
    }

    protected function updateLpa(Lpa $lpa): void
    {
        $this->getLogger()->info('Updating LPA', [
            'lpaid' => $lpa->id
        ]);

        // Check LPA is (still) valid.
        if ($lpa->validateForApi()->hasErrors()) {
            throw new RuntimeException('LPA object is invalid');
        }

        $this->getApplicationRepository()->update($lpa);

        $this->getLogger()->info('LPA updated successfully', [
            'lpaid' => $lpa->id,
            'updatedAt' => $lpa->updatedAt,
        ]);
    }
}
