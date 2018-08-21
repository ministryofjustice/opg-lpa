<?php

namespace Application\Model\Service\WhoAreYou;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryTrait;
use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollectionTrait;
use Application\Model\Service\AbstractService;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use RuntimeException;

class Service extends AbstractService
{
    use ApiLpaCollectionTrait;
    use WhoRepositoryTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ApiProblem|ValidationApiProblem|Entity
     */
    public function create($lpaId, $data)
    {
        $lpa = $this->getLpa($lpaId);

        if ($lpa->whoAreYouAnswered === true) {
            return new ApiProblem(403, 'Question already answered');
        }

        $answer = new WhoAreYou($data);

        $validation = $answer->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa->setWhoAreYouAnswered(true);

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object was created');
        }

        // We update the LPA first as there's a chance a RuntimeException will be thrown if there's an 'updatedAt' mismatch.
        $this->updateLpa($lpa);

        $this->getWhoRepository()->insert($answer);

        return new Entity($lpa->whoAreYouAnswered);
    }
}
