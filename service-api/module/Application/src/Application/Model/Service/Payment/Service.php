<?php

namespace Application\Model\Service\Payment;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use RuntimeException;

class Service extends AbstractService implements LpaConsumerInterface
{
    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|DataModelEntity
     */
    public function update($data, $id)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $lpa->payment = new Payment($data);

        $validation = $lpa->payment->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new DataModelEntity($lpa->payment);
    }
}
