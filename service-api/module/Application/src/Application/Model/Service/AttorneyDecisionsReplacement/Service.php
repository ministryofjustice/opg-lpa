<?php

namespace Application\Model\Service\AttorneyDecisionsReplacement;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
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

        $lpa->document->replacementAttorneyDecisions = new ReplacementAttorneyDecisions($data);

        $validation = $lpa->document->replacementAttorneyDecisions->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new DataModelEntity($lpa->document->replacementAttorneyDecisions);
    }
}
