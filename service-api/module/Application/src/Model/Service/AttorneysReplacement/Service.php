<?php

namespace Application\Model\Service\AttorneysReplacement;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;
use RuntimeException;

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

        $lpa = $this->getLpa($lpaId);

        //  If the client has not passed an id, set it to max(current ids) + 1 - The array is seeded with 0, meaning if this is the first attorney the id will be 1.
        if (is_null($attorney->id)) {
            $ids = [0];

            foreach ($lpa->getDocument()->getReplacementAttorneys() as $a) {
                $ids[] = $a->id;
            }

            $attorney->setId((int) max($ids) + 1);
        }

        $validation = $attorney->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa->getDocument()->replacementAttorneys[] = $attorney;

        $this->updateLpa($lpa);

        return new DataModelEntity($attorney);
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

        foreach ($lpa->getDocument()->getReplacementAttorneys() as $key => $attorney) {
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

                $attorney->setId((int) $id);

                $validation = $attorney->validateForApi();

                if ($validation->hasErrors()) {
                    return new ValidationApiProblem($validation);
                }

                $lpa->getDocument()->replacementAttorneys[$key] = $attorney;

                $this->updateLpa($lpa);

                return new DataModelEntity($attorney);
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

        foreach ($lpa->getDocument()->getReplacementAttorneys() as $key => $attorney) {
            if ($attorney->id == (int) $id) {
                unset($lpa->getDocument()->replacementAttorneys[$key]);

                // Reset the index sequence. This ensure the value remains an array, not an object, in JSON.
                $lpa->getDocument()->setReplacementAttorneys(array_values($lpa->getDocument()->replacementAttorneys));

                $this->updateLpa($lpa);

                return true;
            }
        }

        return new ApiProblem(404, 'Document not found');
    }
}
