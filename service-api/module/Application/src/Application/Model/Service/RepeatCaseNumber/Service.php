<?php

namespace Application\Model\Service\RepeatCaseNumber;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\LpaConsumerInterface;

class Service extends AbstractService implements LpaConsumerInterface
{
    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|Entity
     */
    public function update($data, $id)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $lpa->repeatCaseNumber = (isset($data['repeatCaseNumber']) ? $data['repeatCaseNumber'] : null);

        if (!is_int($lpa->repeatCaseNumber) && is_numeric($lpa->repeatCaseNumber)) {
            $lpa->repeatCaseNumber = (int) $lpa->repeatCaseNumber;
        }

        $validation = $lpa->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->updateLpa($lpa);

        return new Entity($lpa->repeatCaseNumber);
    }

    /**
     * @return ValidationApiProblem|bool
     */
    public function delete()
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $lpa->repeatCaseNumber = null;

        $validation = $lpa->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->updateLpa($lpa);

        return true;
    }
}
