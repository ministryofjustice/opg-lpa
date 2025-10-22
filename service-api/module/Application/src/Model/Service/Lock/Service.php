<?php

namespace Application\Model\Service\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @param $lpaId
     * @return ApiProblem|Entity
     */
    public function create(string $lpaId)
    {
        $lpa = $this->getLpa($lpaId);

        if ($lpa->isLocked()) {
            return new ApiProblem(403, 'LPA already locked');
        }

        $lpa->setLocked(true);
        $lpa->setLockedAt(new MillisecondDateTime());

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object was created');
        }

        $this->updateLpa($lpa);

        return new Entity($lpa);
    }
}
