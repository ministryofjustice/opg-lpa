<?php

namespace Application\Model\Service\Preference;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use RuntimeException;

class Service extends AbstractService
{
    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|Entity
     */
    public function update($lpaId, $data)
    {
        $preference = (isset($data['preference']) ? $data['preference'] : null);

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setPreference($preference);

        $validation = $lpa->getDocument()->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new Entity($preference);
    }
}
