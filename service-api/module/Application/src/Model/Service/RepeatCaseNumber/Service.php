<?php

namespace Application\Model\Service\RepeatCaseNumber;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\EntityInterface;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data): ApiProblem|EntityInterface
    {
        $repeatCaseNumber = (isset($data['repeatCaseNumber']) ? $data['repeatCaseNumber'] : null);

        if (!is_int($repeatCaseNumber) && is_numeric($repeatCaseNumber)) {
            $repeatCaseNumber = (int) $repeatCaseNumber;
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);
        $lpa->setRepeatCaseNumber($repeatCaseNumber);

        $validation = $lpa->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return new Entity($repeatCaseNumber);
    }

    public function delete(string $lpaId, ?int $ifMatchVersion, string $userId): ?ApiProblem
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        $lpa->repeatCaseNumber = null;

        $validation = $lpa->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return null;
    }
}
