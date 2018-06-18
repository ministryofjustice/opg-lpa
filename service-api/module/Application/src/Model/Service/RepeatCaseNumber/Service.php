<?php

namespace Application\Model\Service\RepeatCaseNumber;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;

class Service extends AbstractService
{
    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|Entity
     */
    public function update($lpaId, $data)
    {
        $repeatCaseNumber = (isset($data['repeatCaseNumber']) ? $data['repeatCaseNumber'] : null);

        if (!is_int($repeatCaseNumber) && is_numeric($repeatCaseNumber)) {
            $repeatCaseNumber = (int) $repeatCaseNumber;
        }

        $lpa = $this->getLpa($lpaId);
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
    public function delete($lpaId)
    {
        $lpa = $this->getLpa($lpaId);

        $lpa->repeatCaseNumber = null;

        $validation = $lpa->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->updateLpa($lpa);

        return true;
    }
}
