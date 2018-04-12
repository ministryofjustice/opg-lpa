<?php

namespace Application\Model\Rest\RepeatCaseNumber;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;

class Resource extends AbstractResource implements LpaConsumerInterface
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

        return new Entity($lpa->repeatCaseNumber, $lpa);
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
