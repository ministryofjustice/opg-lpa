<?php

namespace Application\Model\Service\AttorneyDecisionsPrimary;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data): ApiProblem|EntityInterface
    {
        $primaryAttorneyDecisions = new PrimaryAttorneyDecisions($data);

        $validation = $primaryAttorneyDecisions->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);
        $lpa->getDocument()->setPrimaryAttorneyDecisions($primaryAttorneyDecisions);

        $this->assertLpaValid($lpa, 'after setting primary attorney decisions');

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return new DataModelEntity($primaryAttorneyDecisions);
    }
}
