<?php

namespace Application\Model\Service\Payment;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|DataModelEntity
     */
    public function update($lpaId, $data)
    {
        $payment = new Payment($data);

        $validation = $payment->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->setPayment($payment);

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new DataModelEntity($payment);
    }
}
