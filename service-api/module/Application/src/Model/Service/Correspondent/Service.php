<?php

namespace Application\Model\Service\Correspondent;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data): ApiProblem|EntityInterface
    {
        $correspondent = new Correspondence($data);

        $validation = $correspondent->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);
        $lpa->getDocument()->setCorrespondent($correspondent);

        $this->assertLpaValid($lpa, 'after setting correspondent');

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return new DataModelEntity($correspondent);
    }

    public function delete(string $lpaId, ?int $ifMatchVersion, string $userId): ?ApiProblem
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        $lpa->getDocument()->correspondent = null;

        $validation = $lpa->getDocument()->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->assertLpaValid($lpa, 'after deleting correspondent');

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return null;
    }
}
