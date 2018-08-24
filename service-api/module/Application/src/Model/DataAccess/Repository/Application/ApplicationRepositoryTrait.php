<?php
namespace Application\Model\DataAccess\Repository\Application;

use RuntimeException;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Logger\Logger;

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

    /**
     * @return ApplicationRepositoryInterface
     */
    private function getApplicationRepository() : ApplicationRepositoryInterface
    {
        if (!($this->applicationRepository instanceof WhoRepositoryInterface)) {
            throw new \RuntimeException("Instance of ApplicationRepository not set");
        }

        return $this->applicationRepository;
    }

    protected function getLpa(int $lpaId) : ?Lpa
    {
        $result = $this->getApplicationRepository()->getById($lpaId);

        if (is_null($result)) {
            return null;
        }

        return new Lpa($result);
    }

    protected function updateLpa(Lpa $lpa) : bool
    {
        $logger = Logger::getInstance();

        $logger->info('Updating LPA', [
            'lpaid' => $lpa->id
        ]);

        // Check LPA is (still) valid.
        if ($lpa->validateForApi()->hasErrors()) {
            throw new RuntimeException('LPA object is invalid');
        }

        $result = $this->getApplicationRepository()->update($lpa);

        $logger->info('LPA updated successfully', [
            'lpaid' => $lpa->id,
            'updatedAt' => $lpa->updatedAt,
        ]);

        return $result;
    }

}
