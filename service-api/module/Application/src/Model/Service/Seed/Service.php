<?php

namespace Application\Model\Service\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\DataModelEntity;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @var ApplicationService
     */
    private $applicationsService;

    /**
     * @param $lpaId
     * @param $userId
     * @return ApiProblem|Entity
     */
    public function fetch($lpaId, $userId)
    {
        $lpa = $this->getLpa($lpaId);

        $lpaEntity = $this->applicationsService->fetch($lpa->seed, $userId);

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

    /**
     * @param $lpaId
     * @param $data
     * @param $userId
     * @return ApiProblem|Entity
     */
    public function update($lpaId, $data, $userId)
    {
        if (!isset($data['seed']) || !is_numeric($data['seed'])) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        //  TODO - Change this to just use the getLpa method in the parent abstract controller?
        $lpaEntity = $this->applicationsService->fetch($data['seed'], $userId);

        if (!($lpaEntity instanceof DataModelEntity)) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        /** @var Lpa $seedLpa */
        $seedLpa = $lpaEntity->getData();

        // Shouldn't need to check this, but just to be safe...
        $lpa = $this->getLpa($lpaId);

        if ($seedLpa->user != $lpa->user) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        $lpa->seed = $seedLpa->id;

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new Entity($seedLpa);
    }

    /**
     * @param ApplicationService $applicationsService
     */
    public function setApplicationsService(ApplicationService $applicationsService)
    {
        $this->applicationsService = $applicationsService;
    }
}
