<?php

namespace Application\Model\Service\CertificateProvider;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use RuntimeException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|DataModelEntity
     */
    public function update($lpaId, $data)
    {
        $certificateProvider = new CertificateProvider($data);

        $validation = $certificateProvider->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setCertificateProvider($certificateProvider);

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new DataModelEntity($certificateProvider);
    }

    /**
     * @param $lpaId
     * @return ValidationApiProblem|bool
     */
    public function delete($lpaId)
    {
        $lpa = $this->getLpa($lpaId);

        $lpa->getDocument()->certificateProvider = null;

        $validation = $lpa->getDocument()->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return true;
    }
}
