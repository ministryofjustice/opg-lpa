<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use Application\Model\Service\Lock\LockedException;
use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;

trait ApiLpaCollectionTrait
{
    /**
     * @var ApiLpaCollection
     */
    private $apiLpaCollection;

    /**
     * @param ApiLpaCollection $apiLpaCollection
     * @return $this
     */
    public function setApiLpaCollection(ApiLpaCollection $apiLpaCollection)
    {
        $this->apiLpaCollection = $apiLpaCollection;

        return $this;
    }

    /**
     * @param $lpaId
     * @return null|Lpa
     */
    protected function getLpa($lpaId)
    {
        $result = $this->apiLpaCollection->getById((int) $lpaId);

        if (is_null($result)) {
            return null;
        }

        $result = [
                'id' => $result['_id']
            ] + $result;

        return new Lpa($result);
    }

    /**
     * Helper method for saving an updated LPA.
     *
     * @param Lpa $lpa
     */
    protected function updateLpa(Lpa $lpa)
    {
//        $this->getLogger()->info('Updating LPA', [
//            'lpaid' => $lpa->id
//        ]);

        // Check LPA is (still) valid.
        if ($lpa->validateForApi()->hasErrors()) {
            throw new RuntimeException('LPA object is invalid');
        }

        // Check LPA in database isn't locked...
        $existingLpa = $this->getLpa($lpa->id);

        if ($existingLpa instanceof Lpa && $existingLpa->isLocked()) {
            throw new LockedException('LPA has already been locked.');
        }

        //  Only update the timestamp if the LPA document itself has changed
        $updateTimestamp = (is_null($existingLpa) || !$lpa->equalsIgnoreMetadata($existingLpa));

        $this->apiLpaCollection->update($lpa, $updateTimestamp);

//        $this->getLogger()->info('LPA updated successfully', [
//            'lpaid' => $lpa->id,
//            'updatedAt' => $lpa->updatedAt,
//        ]);
    }
}
