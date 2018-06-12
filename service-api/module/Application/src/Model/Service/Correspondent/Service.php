<?php

namespace Application\Model\Service\Correspondent;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use RuntimeException;

class Service extends AbstractService
{
    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|DataModelEntity
     */
    public function update($lpaId, $data)
    {
        $correspondent = new Correspondence($data);

        $validation = $correspondent->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setCorrespondent($correspondent);

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new DataModelEntity($correspondent);
    }

    /**
     * @param $lpaId
     * @return ValidationApiProblem|bool
     */
    public function delete($lpaId)
    {
        $lpa = $this->getLpa($lpaId);

        $lpa->getDocument()->correspondent = null;

        $validation = $lpa->getDocument()->validate();

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
