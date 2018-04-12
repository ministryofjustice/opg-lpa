<?php

namespace Application\Model\Rest\NotifiedPeople;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;

class Resource extends AbstractResource implements LpaConsumerInterface
{
    /**
     * @param $data
     * @return ValidationApiProblem|Entity
     */
    public function create($data)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $person = new NotifiedPerson($data);

        //  If the client has not passed an id, set it to max(current ids) + 1 - The array is seeded with 0, meaning if this is the first attorney the id will be 1.
        if (is_null($person->id)) {
            $ids = array(0);
            foreach ($lpa->document->peopleToNotify as $a) {
                $ids[] = $a->id;
            }

            $person->id = (int) max($ids) + 1;
        }

        $validation = $person->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa->document->peopleToNotify[] = $person;

        $this->updateLpa($lpa);

        return new Entity($person, $lpa);
    }

    /**
     * @param $data
     * @param $id
     * @return ApiProblem|ValidationApiProblem|Entity
     */
    public function update($data, $id)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        foreach ($lpa->document->peopleToNotify as $key => $person) {
            if ($person->id == (int) $id) {
                $person = new NotifiedPerson($data);

                $person->id = (int) $id;

                $validation = $person->validateForApi();

                if ($validation->hasErrors()) {
                    return new ValidationApiProblem($validation);
                }

                $lpa->document->peopleToNotify[$key] = $person;

                $this->updateLpa($lpa);

                return new Entity($person, $lpa);
            }
        }

        return new ApiProblem(404, 'Document not found');
    }

    /**
     * @param $id
     * @return ApiProblem|bool
     */
    public function delete($id)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        foreach ($lpa->document->peopleToNotify as $key => $person) {
            if ($person->id == (int) $id) {
                unset($lpa->document->peopleToNotify[$key]);

                $this->updateLpa($lpa);

                return true;
            }
        }

        return new ApiProblem(404, 'Document not found');
    }
}
