<?php

namespace Application\Model\Service\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;

class Service extends AbstractService implements LpaConsumerInterface
{
    /**
     * @var ApplicationService
     */
    private $applicationsService;

    /**
     * @return ApiProblem|Entity
     */
    public function fetch()
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        if (!is_int($lpa->seed)) {
            return new Entity(null);
        }

        $lpaEntity = $this->applicationsService->fetch($lpa->seed);

        if (!($lpaEntity instanceof DataModelEntity)) {
            return new ApiProblem(404, 'Invalid LPA identifier to seed from');
        }

        /** @var Lpa $seedLpa */
        $seedLpa = $lpaEntity->getData();

        //  Should need to check this, but just to be safe...
        if ($seedLpa->user != $lpa->user) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        return new Entity($seedLpa);
    }

    /**
     * @param  $data
     * @return ApiProblem|Entity
     */
    public function update($data)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        if (!isset($data['seed']) || !is_numeric($data['seed'])) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        $lpaEntity = $this->applicationsService->fetch($data['seed']);

        if (!($lpaEntity instanceof DataModelEntity)) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        /** @var Lpa $seedLpa */
        $seedLpa = $lpaEntity->getData();

        // Should need to check this, but just to be safe...
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
