<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Logger\Logger;
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
        $logger = Logger::getInstance();

        $logger->info('Updating LPA', [
            'lpaid' => $lpa->id
        ]);

        // Check LPA is (still) valid.
        if ($lpa->validateForApi()->hasErrors()) {
            throw new RuntimeException('LPA object is invalid');
        }

        $this->apiLpaCollection->update($lpa);

        $logger->info('LPA updated successfully', [
            'lpaid' => $lpa->id,
            'updatedAt' => $lpa->updatedAt,
        ]);
    }
}
