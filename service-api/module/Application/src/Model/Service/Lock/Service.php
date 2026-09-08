<?php

namespace Application\Model\Service\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @param $lpaId
     * @return ApiProblem|Entity
     */
    public function create(string $lpaId, ?int $ifMatchVersion, string $userId)
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        if ($lpa->isLocked()) {
            return new ApiProblem(403, 'LPA already locked');
        }

        $lpa->setLocked(true);
        $lpa->setLockedAt(new MillisecondDateTime());

        $this->assertLpaValid($lpa, 'after locking LPA');

        $this->updateLpa($lpa);

        return new Entity($lpa);
    }
}
