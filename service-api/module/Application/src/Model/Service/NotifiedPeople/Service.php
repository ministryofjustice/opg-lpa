<?php

namespace Application\Model\Service\NotifiedPeople;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;

    /**
     * @param $lpaId
     * @param $data
     * @return ValidationApiProblem|DataModelEntity
     */
    public function create($lpaId, $data)
    {
        $lpa = $this->getLpa($lpaId);

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

        $this->updateLpa($lpa);

        return new DataModelEntity($person);
    }

    /**
     * @param $lpaId
     * @param $data
     * @param $id
     * @return ApiProblem|ValidationApiProblem|DataModelEntity
     */
    public function update($lpaId, $data, $id)
    {
        $lpa = $this->getLpa($lpaId);

        foreach ($lpa->getDocument()->getPeopleToNotify() as $key => $person) {
            if ($person->id == (int) $id) {
                $person = new NotifiedPerson($data);

                $person->id = (int) $id;

                $validation = $person->validateForApi();

                if ($validation->hasErrors()) {
                    return new ValidationApiProblem($validation);
                }

                $lpa->getDocument()->peopleToNotify[$key] = $person;

                $this->updateLpa($lpa);

                return new DataModelEntity($person);
            }
        }

        return new ApiProblem(404, 'Document not found');
    }

    /**
     * @param $lpaId
     * @param $id
     * @return ApiProblem|bool
     */
    public function delete($lpaId, $id)
    {
        $lpa = $this->getLpa($lpaId);

        foreach ($lpa->getDocument()->getPeopleToNotify() as $key => $person) {
            if ($person->id == (int) $id) {
                unset($lpa->getDocument()->peopleToNotify[$key]);

                $this->updateLpa($lpa);

                return true;
            }
        }

        return new ApiProblem(404, 'Document not found');
    }
}
