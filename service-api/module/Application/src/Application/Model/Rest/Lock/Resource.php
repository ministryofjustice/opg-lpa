<?php

namespace Application\Model\Rest\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\DateTime;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use RuntimeException;

class Resource extends AbstractResource implements LpaConsumerInterface
{
    /**
     * @param $data
     * @return ApiProblem|Entity
     */
    public function create($data)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        if ($lpa->locked === true) {
            return new ApiProblem(403, 'LPA already locked');
        }

        $lpa->locked = true;
        $lpa->lockedAt = new DateTime();

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object was created');
        }

        $this->updateLpa($lpa);

        return new Entity($lpa->locked, $lpa);
    }
}
