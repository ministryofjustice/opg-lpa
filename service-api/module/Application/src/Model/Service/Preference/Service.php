<?php

namespace Application\Model\Service\Preference;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\LpaConsumerInterface;
use RuntimeException;

class Service extends AbstractService implements LpaConsumerInterface
{
    /**
     * @param $data
     * @return ValidationApiProblem|Entity
     */
    public function update($data)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $lpa->document->preference = (isset($data['preference']) ? $data['preference'] : null);

        $validation = $lpa->document->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new Entity($lpa->document->preference);
    }
}
