<?php

namespace Application\Model\Service\NotifiedPeople;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    public function create(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data): ApiProblem|EntityInterface
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        $person = new NotifiedPerson($data);

        //  If the client has not passed an id, set it to max(current ids) + 1 - The array is seeded with 0, meaning if this is the first attorney the id will be 1.
        if (is_null($person->id)) {
            $ids = [0];

            foreach ($lpa->getDocument()->getPeopleToNotify() as $a) {
                $ids[] = $a->id;
            }

            $person->setId((int) max($ids) + 1);
        }

        $validation = $person->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa->getDocument()->peopleToNotify[] = $person;

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return new DataModelEntity($person);
    }

    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data, mixed $id): ApiProblem|EntityInterface
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        foreach ($lpa->getDocument()->getPeopleToNotify() as $key => $person) {
            if ($person->id == (int) $id) {
                $person = new NotifiedPerson($data);

                $person->id = (int) $id;

                $validation = $person->validateForApi();

                if ($validation->hasErrors()) {
                    return new ValidationApiProblem($validation);
                }

                $lpa->getDocument()->peopleToNotify[$key] = $person;

                try {
                    $this->updateLpa($lpa);
                } catch (ConflictException $e) {
                    return new ConflictProblem($e);
                }

                return new DataModelEntity($person);
            }
        }

        return new ApiProblem(404, 'Document not found');
    }

    public function delete(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $id): ?ApiProblem
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        foreach ($lpa->getDocument()->getPeopleToNotify() as $key => $person) {
            if ($person->id == (int) $id) {
                unset($lpa->getDocument()->peopleToNotify[$key]);

                // Reset the index sequence. This ensure the value remains an array, not an object, in JSON.
                $lpa->getDocument()->setPeopleToNotify(array_values($lpa->getDocument()->peopleToNotify));

                try {
                    $this->updateLpa($lpa);
                } catch (ConflictException $e) {
                    return new ConflictProblem($e);
                }

                return null;
            }
        }

        return new ApiProblem(404, 'Document not found');
    }
}
