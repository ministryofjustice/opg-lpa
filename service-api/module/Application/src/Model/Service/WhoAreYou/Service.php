<?php

namespace Application\Model\Service\WhoAreYou;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use WhoRepositoryTrait;
    use LoggerTrait;

    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data): ApiProblem|EntityInterface
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        if ($lpa->whoAreYouAnswered === true) {
            return new ApiProblem(403, 'Question already answered');
        }

        $answer = new WhoAreYou($data);

        $validation = $answer->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }
        $lpa->setWhoAreYouAnswered(true);

        $this->assertLpaValid($lpa, 'after setting whoAreYouAnswered');

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        $this->getWhoRepository()->insert($answer);

        return new Entity($lpa->whoAreYouAnswered);
    }
}
