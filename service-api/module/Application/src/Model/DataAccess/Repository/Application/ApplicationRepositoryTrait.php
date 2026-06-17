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
        $validation = $lpa->validateForApi();

        if ($validation->hasErrors()) {
            $validationErrors = $validation->getArrayCopy();

            $this->getLogger()->debug('LPA validation failed during update', [
                'lpaid' => $lpa->id,
                'validation_errors' => $validationErrors,
                'lpa_type' => $lpa->getDocument()->getType(),
                'lpa_has_donor' => $lpa->getDocument()->getDonor() !== null,
                'lpa_has_certificate_provider' => $lpa->getDocument()->getCertificateProvider() !== null,
                'lpa_primary_attorneys_count' => count($lpa->getDocument()->getPrimaryAttorneys()),
                'lpa_replacement_attorneys_count' => count($lpa->getDocument()->getReplacementAttorneys()),
                'lpa_has_correspondent' => $lpa->getDocument()->getCorrespondent() !== null,
            ]);

            throw new RuntimeException(sprintf(
                'LPA object is invalid. LPA ID: %s. Validation errors: %s',
                $lpa->id,
                (string) json_encode($validationErrors)
            ));
        }

        $this->getApplicationRepository()->update($lpa);

        $this->getLogger()->info('LPA updated successfully', [
            'lpaid' => $lpa->id,
            'updatedAt' => $lpa->updatedAt,
        ]);
    }
}
