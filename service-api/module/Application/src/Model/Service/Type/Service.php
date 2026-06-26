<?php

namespace Application\Model\Service\Type;

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
    public function update(string $lpaId, $data)
    {
        $type = (isset($data['type']) ? $data['type'] : null);

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setType($type);

        $validation = $lpa->getDocument()->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->assertLpaValid($lpa, 'after setting type');

        $this->updateLpa($lpa);

        return new Entity($type);
    }
}
