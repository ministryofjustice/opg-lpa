<?php

namespace Application\Model\Rest\Correspondent;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use RuntimeException;

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

        $lpa->document->correspondent = (isset($data['correspondent']) ? new Correspondence($data['correspondent']) : null);
        $lpa->document->correspondent = new Correspondence($data);

        $validation = $lpa->document->correspondent->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new Entity($lpa->document->correspondent, $lpa);
    }

    /**
     * @return ValidationApiProblem|bool
     */
    public function delete()
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $lpa->document->correspondent = null;

        $validation = $lpa->document->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return true;
    }
}
