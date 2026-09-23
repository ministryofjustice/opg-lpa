<?php

namespace Application\Model\Service\WhoIsRegistering;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|Entity
     */
    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, $data)
    {
        $whoIsRegistering = (isset($data['whoIsRegistering']) ? $data['whoIsRegistering'] : null);

        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);
        $lpa->getDocument()->setWhoIsRegistering($whoIsRegistering);

        $validation = $lpa->getDocument()->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->assertLpaValid($lpa, 'after setting whoIsRegistering');

        $this->updateLpa($lpa);

        return new Entity($whoIsRegistering);
    }
}
