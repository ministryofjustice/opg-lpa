<?php

namespace Application\Model\Service\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\DateTime;
use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollectionTrait;
use Application\Model\Service\AbstractService;
use RuntimeException;

class Service extends AbstractService
{
    use ApiLpaCollectionTrait;

    /**
     * @param $lpaId
     * @return ApiProblem|Entity
     */
    public function create($lpaId)
    {
        $lpa = $this->getLpa($lpaId);

        if ($lpa->isLocked()) {
            return new ApiProblem(403, 'LPA already locked');
        }

        $lpa->setLocked(true);
        $lpa->setLockedAt(new DateTime());

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object was created');
        }

        $this->updateLpa($lpa);

        return new Entity($lpa);
    }
}
