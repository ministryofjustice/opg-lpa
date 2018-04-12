<?php

namespace Application\Model\Rest\Payment;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\Logger\LoggerTrait;
use RuntimeException;

class Resource extends AbstractResource implements LpaConsumerInterface
{


    use LoggerTrait;

    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|Entity
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

        return new Entity($lpa->payment, $lpa);
    }
}
