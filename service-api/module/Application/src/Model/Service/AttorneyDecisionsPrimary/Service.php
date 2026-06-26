<?php

namespace Application\Model\Service\AttorneyDecisionsPrimary;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
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
        $primaryAttorneyDecisions = new PrimaryAttorneyDecisions($data);

        $validation = $primaryAttorneyDecisions->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setPrimaryAttorneyDecisions($primaryAttorneyDecisions);

        $this->assertLpaValid($lpa, 'after setting primary attorney decisions');

        $this->updateLpa($lpa);

        return new DataModelEntity($primaryAttorneyDecisions);
    }
}
