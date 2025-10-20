<?php

namespace Application\Model\Service\WhoAreYou;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryTrait;
use Application\Model\Service\AbstractService;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use WhoRepositoryTrait;
    use LoggerTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ApiProblem|ValidationApiProblem|Entity
     */
    public function update(string $lpaId, $data)
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
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        $this->getWhoRepository()->insert($answer);

        return new Entity($lpa->whoAreYouAnswered);
    }
}
