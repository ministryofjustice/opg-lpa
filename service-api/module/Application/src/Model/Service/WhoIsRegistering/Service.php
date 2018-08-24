<?php

namespace Application\Model\Service\WhoIsRegistering;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use RuntimeException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|Entity
     */
    public function update($lpaId, $data)
    {
        $whoIsRegistering = (isset($data['whoIsRegistering']) ? $data['whoIsRegistering'] : null);

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setWhoIsRegistering($whoIsRegistering);

        $validation = $lpa->getDocument()->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new Entity($whoIsRegistering);
    }
}
