<?php

namespace Application\Model\Service\Donor;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
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
        $donor = new Donor($data);

        $validation = $donor->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setDonor($donor);

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new DataModelEntity($donor);
    }
}
