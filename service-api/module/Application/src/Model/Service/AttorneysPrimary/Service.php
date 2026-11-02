<?php

namespace Application\Model\Service\AttorneysPrimary;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ConflictProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\Lpa\Document\Attorneys;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    public function create(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data): ApiProblem|EntityInterface
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
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        //  If the client has not passed an id, set it to max(current ids) + 1 - The array is seeded with 0, meaning if this is the first attorney the id will be 1.
        if (is_null($attorney->id)) {
            $ids = [0];

            foreach ($lpa->getDocument()->getPrimaryAttorneys() as $a) {
                $ids[] = $a->id;
            }

            $attorney->setId((int) max($ids) + 1);
        }

        $validation = $attorney->validateForApi();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $lpa->getDocument()->primaryAttorneys[] = $attorney;

        try {
            $this->updateLpa($lpa);
        } catch (ConflictException $e) {
            return new ConflictProblem($e);
        }

        return new DataModelEntity($attorney);
    }

    public function update(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $data, mixed $id): ApiProblem|EntityInterface
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        foreach ($lpa->getDocument()->getPrimaryAttorneys() as $key => $attorney) {
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

                $lpa->getDocument()->primaryAttorneys[$key] = $attorney;

                try {
                    $this->updateLpa($lpa);
                } catch (ConflictException $e) {
                    return new ConflictProblem($e);
                }

                return new DataModelEntity($attorney);
            }
        }

        return new ApiProblem(404, 'Document not found');
    }

    public function delete(string $lpaId, ?int $ifMatchVersion, string $userId, mixed $id): ?ApiProblem
    {
        $lpa = $this->getLpa($lpaId);
        $lpa->setVersion($ifMatchVersion);
        $lpa->setUpdatedBy($userId);

        foreach ($lpa->getDocument()->getPrimaryAttorneys() as $key => $attorney) {
            if ($attorney->id == (int) $id) {
                unset($lpa->getDocument()->primaryAttorneys[$key]);

                // Reset the index sequence. This ensure the value remains an array, not an object, in JSON.
                $lpa->getDocument()->setPrimaryAttorneys(array_values($lpa->getDocument()->primaryAttorneys));

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
