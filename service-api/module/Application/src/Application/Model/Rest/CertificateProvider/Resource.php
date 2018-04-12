<?php

namespace Application\Model\Rest\CertificateProvider;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use RuntimeException;

class Resource extends AbstractResource implements LpaConsumerInterface
{
    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|Entity
     */
    public function update($data, $id)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $lpa->document->certificateProvider = new CertificateProvider($data);

        $validation = $lpa->document->certificateProvider->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new Entity($lpa->document->certificateProvider, $lpa);
    }

    /**
     * @return ValidationApiProblem|bool
     */
    public function delete()
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $lpa->document->certificateProvider = null;

        $validation = $lpa->document->validate();

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
