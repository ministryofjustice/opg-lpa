<?php

namespace Application\Model\Service\Correspondent;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use RuntimeException;

class Service extends AbstractService implements LpaConsumerInterface
{
    /**
     * @param $data
     * @return ValidationApiProblem|DataModelEntity
     */
    public function update($data)
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

        return new DataModelEntity($lpa->document->correspondent);
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
