<?php

namespace Application\Model\Service\AttorneyDecisionsReplacement;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|DataModelEntity
     */
    public function update(string $lpaId, $data)
    {
        $replacementAttorneyDecisions = new ReplacementAttorneyDecisions($data);

        $validation = $replacementAttorneyDecisions->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setReplacementAttorneyDecisions($replacementAttorneyDecisions);

        $this->assertLpaValid($lpa, 'after setting replacement attorney decisions');

        $this->updateLpa($lpa);

        return new DataModelEntity($replacementAttorneyDecisions);
    }
}
