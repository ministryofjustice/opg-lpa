<?php

namespace Application\Model\Rest\AttorneysPrimary;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;
use RuntimeException;

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

        switch ($data['type']) {
            case 'trust':
                $attorney = new Attorneys\TrustCorporation($data);
                break;
            case 'human':
                $attorney = new Attorneys\Human($data);
                break;
            default:
                // TODO - return a ValidationApiProblem?
                throw new RuntimeException('Invalid type passed');
        }

        //  If the client has not passed an id, set it to max(current ids) + 1 - The array is seeded with 0, meaning if this is the first attorney the id will be 1.
        if (is_null($attorney->id)) {
            $ids = [0];

            foreach ($lpa->document->primaryAttorneys as $a) {
                $ids[] = $a->id;
            }

            $attorney->id = (int) max($ids) + 1;
        }

        $validation = $attorney->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa->document->primaryAttorneys[] = $attorney;

        $this->updateLpa($lpa);

        return new Entity($attorney, $lpa);
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

        foreach ($lpa->document->primaryAttorneys as $key => $attorney) {
            if ($attorney->id == (int) $id) {
                switch ($data['type']) {
                    case 'trust':
                        $attorney = new Attorneys\TrustCorporation($data);
                        break;
                    case 'human':
                        $attorney = new Attorneys\Human($data);
                        break;
                    default:
                        // TODO - return a ValidationApiProblem?
                        throw new RuntimeException('Invalid type passed');
                }

                $attorney->id = (int) $id;

                $validation = $attorney->validateForApi();

                if ($validation->hasErrors()) {
                    return new ValidationApiProblem($validation);
                }

                $lpa->document->primaryAttorneys[$key] = $attorney;

                $this->updateLpa($lpa);

                return new Entity($attorney, $lpa);
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

        foreach ($lpa->document->primaryAttorneys as $key => $attorney) {
            if ($attorney->id == (int) $id) {
                unset($lpa->document->primaryAttorneys[$key]);

                $this->updateLpa($lpa);

                return true;
            }
        }

        return new ApiProblem(404, 'Document not found');
    }
}
