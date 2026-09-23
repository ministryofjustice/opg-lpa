<?php

namespace Application\Model\Service\RepeatCaseNumber;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|Entity
     */
    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, $data)
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

        $this->updateLpa($lpa);

        return new Entity($repeatCaseNumber);
    }

    /**
     * @param $lpaId
     * @return ValidationApiProblem|bool
     */
    public function delete(string $lpaId, ?int $ifMatchVersion, string $userId)
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        $lpa->repeatCaseNumber = null;

        $validation = $lpa->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->updateLpa($lpa);

        return true;
    }
}
