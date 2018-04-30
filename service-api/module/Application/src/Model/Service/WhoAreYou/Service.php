<?php

namespace Application\Model\Service\WhoAreYou;

use Application\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AbstractService;
use Application\Model\Service\LpaConsumerInterface;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use RuntimeException;

class Service extends AbstractService implements LpaConsumerInterface
{
    /**
     * @param $data
     * @return ApiProblem|ValidationApiProblem|Entity
     */
    public function create($data)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        if ($lpa->whoAreYouAnswered === true) {
            return new ApiProblem(403, 'Question already answered');
        }

        $answer = new WhoAreYou($data);

        $validation = $answer->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa->whoAreYouAnswered = true;

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object was created');
        }

        // We update the LPA first as there's a chance a RuntimeException will be thrown if there's an 'updatedAt' mismatch.
        $this->updateLpa($lpa);

        $this->collection->insertOne($answer->toArray(new DateCallback()));

        return new Entity($lpa->whoAreYouAnswered);
    }
}
