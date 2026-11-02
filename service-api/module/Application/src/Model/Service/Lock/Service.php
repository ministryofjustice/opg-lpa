<?php

namespace Application\Model\Service\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\EntityInterface;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    public function create(string $lpaId, ?int $ifMatchVersion, string $userId): ApiProblem|EntityInterface
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

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return new Entity($lpa);
    }
}
