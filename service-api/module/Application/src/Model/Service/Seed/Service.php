<?php

namespace Application\Model\Service\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    private ApplicationService $applicationsService;

    public function fetch(string $lpaId, string $userId): ApiProblem|EntityInterface
    {
        $lpa = $this->getLpa($lpaId);

        $lpaEntity = $this->applicationsService->fetch(strval($lpa->seed), $userId);

        if (!($lpaEntity instanceof DataModelEntity)) {
            return new ApiProblem(404, 'Invalid LPA identifier to seed from');
        }

        /** @var Lpa $seedLpa */
        $seedLpa = $lpaEntity->getData();

        if ($seedLpa->user != $lpa->user) {
            return new ApiProblem(400, 'LPA user does not match fetched LPA\'s user');
        }

        return new Entity($seedLpa);
    }

    public function update(string $lpaId, $data, ?int $ifMatchVersion, string $userId): ApiProblem|EntityInterface
    {
        if (!isset($data['seed']) || !is_numeric($data['seed'])) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        //  TODO - Change this to just use the getLpa method in the parent abstract controller?
        $lpaEntity = $this->applicationsService->fetch(strval($data['seed']), $userId);

        if (!($lpaEntity instanceof DataModelEntity)) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        /** @var Lpa $seedLpa */
        $seedLpa = $lpaEntity->getData();

        // Shouldn't need to check this, but just to be safe...
        $lpa = $this->getLpa($lpaId);

        if (is_null($lpa)) {
            return new ApiProblem(404, 'LPA not found');
        }

        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        if ($seedLpa->user != $lpa->user) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        $lpa->seed = $seedLpa->id;

        $this->assertLpaValid($lpa, 'after setting seed');

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return new Entity($seedLpa);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setApplicationsService(ApplicationService $applicationsService): void
    {
        $this->applicationsService = $applicationsService;
    }
}
