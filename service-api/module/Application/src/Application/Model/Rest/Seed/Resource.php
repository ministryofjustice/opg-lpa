<?php

namespace Application\Model\Rest\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Applications\Entity as ApplicationEntity;
use Application\Model\Rest\Applications\Resource as ApplicationResource;
use Application\Model\Rest\LpaConsumerInterface;
use RuntimeException;

class Resource extends AbstractResource implements LpaConsumerInterface
{
    /**
     * @var ApplicationResource
     */
    private $applicationsResource;

    /**
     * @return ApiProblem|Entity
     */
    public function fetch()
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        if (!is_int($lpa->seed)) {
            return new Entity(null, $lpa);
        }

        $lpaEntity  = $this->applicationsResource->fetch($lpa->seed);

        if (!($lpaEntity instanceof ApplicationEntity)) {
            return new ApiProblem(404, 'Invalid LPA identifier to seed from');
        }

        $seedLpa = $lpaEntity->getLpa();

        //  Should need to check this, but just to be safe...
        if ($seedLpa->user != $lpa->user) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        return new Entity($seedLpa, $lpa);
    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|Entity
     */
    public function update($data, $id)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        if (!isset($data['seed']) || !is_numeric($data['seed'])) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        $lpaEntity = $this->applicationsResource->fetch($data['seed']);

        if (!($lpaEntity instanceof ApplicationEntity)) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        $seedLpa = $lpaEntity->getLpa();

        // Should need to check this, but just to be safe...
        if ($seedLpa->user != $lpa->user) {
            return new ApiProblem(400, 'Invalid LPA identifier to seed from');
        }

        $lpa->seed = $seedLpa->id;

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new Entity($seedLpa, $lpa);
    }

    /**
     * @param ApplicationResource $applicationsResource
     */
    public function setApplicationsResource(ApplicationResource $applicationsResource)
    {
        $this->applicationsResource = $applicationsResource;
    }
}
