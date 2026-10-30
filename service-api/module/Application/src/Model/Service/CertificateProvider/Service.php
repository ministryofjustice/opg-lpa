<?php

namespace Application\Model\Service\CertificateProvider;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data): ApiProblem|EntityInterface
    {
        $certificateProvider = new CertificateProvider($data);

        $validation = $certificateProvider->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);
        $lpa->getDocument()->setCertificateProvider($certificateProvider);

        $this->assertLpaValid($lpa, 'after setting certificate provider');

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return new DataModelEntity($certificateProvider);
    }

    public function delete(string $lpaId, ?int $ifMatchVersion, string $userId): ?ApiProblem
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        $lpa->getDocument()->certificateProvider = null;

        $validation = $lpa->getDocument()->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->assertLpaValid($lpa, 'after deleting certificate provider');

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return null;
    }
}
