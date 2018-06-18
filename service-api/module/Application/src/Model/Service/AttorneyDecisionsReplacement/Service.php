<?php

namespace Application\Model\Service\AttorneyDecisionsReplacement;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
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
        $replacementAttorneyDecisions = new ReplacementAttorneyDecisions($data);

        $validation = $replacementAttorneyDecisions->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setReplacementAttorneyDecisions($replacementAttorneyDecisions);

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new DataModelEntity($replacementAttorneyDecisions);
    }
}
