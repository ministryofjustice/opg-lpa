<?php

namespace Application\Model\Service;

use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\DateTime;
use Application\Model\Service\Lock\LockedException;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Logger\LoggerTrait;
use ZfcRbac\Service\AuthorizationServiceAwareInterface;
use ZfcRbac\Service\AuthorizationServiceAwareTrait;
use RuntimeException;

abstract class AbstractService implements AuthorizationServiceAwareInterface
{
    use LoggerTrait;
    use AuthorizationServiceAwareTrait;

    /**
     * @var Lpa
     */
    protected $lpa = null;

    /**
     * @var string
     */
    protected $routeUserId = null;

    /**
     * @var Collection
     */
    protected $lpaCollection = null;

    /**
     * @var Collection
     */
    protected $collection = null;

    /**
     * AbstractService constructor
     *
     * @param string $routeUserId
     * @param Collection $lpaCollection
     * @param Collection|null $collection
     */
    public function __construct($routeUserId, Collection $lpaCollection, Collection $collection = null)
    {
        $this->routeUserId = $routeUserId;
        $this->lpaCollection = $lpaCollection;
        $this->collection = $collection;
    }

    public function setLpa(Lpa $lpa)
    {
        $this->lpa = $lpa;
    }

    /**
     * @return Lpa
     */
    public function getLpa()
    {
        if (!$this->lpa instanceof Lpa) {
            throw new RuntimeException('LPA not set');
        }

        return $this->lpa;
    }

    public function checkAccess($userId = null)
    {
        if (is_null($userId) && $this->routeUserId != null) {
            $userId = $this->routeUserId;
        }

        if (!$this->getAuthorizationService()->isGranted('authenticated')) {
            throw new UnauthorizedException('You need to be authenticated to access this service');
        }

        if (!$this->getAuthorizationService()->isGranted('isAuthorizedToManageUser', $userId)
            && !$this->getAuthorizationService()->isGranted('admin')) {

            throw new UnauthorizedException('You do not have permission to access this service');
        }
    }

    /**
     * Helper method for saving an updated LPA.
     *
     * @param Lpa $lpa
     */
    protected function updateLpa(Lpa $lpa)
    {
        $this->getLogger()->info('Updating LPA', [
            'lpaid' => $lpa->id
        ]);

        // Should already have been checked, but no harm checking again.
        $this->checkAccess();

        // Check LPA is (still) valid.
        if ($lpa->validateForApi()->hasErrors()) {
            throw new RuntimeException('LPA object is invalid');
        }

        // Check LPA in database isn't locked...
        $locked = ($this->lpaCollection->count([
                '_id' => $lpa->id,
                'locked' => true,
            ], [
                '_id' => true,
            ] ) > 0);

        if ($locked === true) {
            throw new LockedException('LPA has already been locked.');
        }

        // If instrument created, record the date.
        $isCreated = $lpa->isStateCreated();

        if ($isCreated) {
            $this->getLogger()->info('LPA is created', [
                'lpaid' => $lpa->id
            ]);

            if (!($lpa->createdAt instanceof \DateTime)) {
                $this->getLogger()->info('Setting created time for existing LPA', [
                    'lpaid' => $lpa->id
                ]);

                $lpa->createdAt = new DateTime();
            }
        } else {
            $this->getLogger()->info('LPA is not fully created', [
                'lpaid' => $lpa->id
            ]);

            $lpa->createdAt = null;
        }

        // If completed, record the date.
        $isCompleted = $lpa->isStateCompleted();

        if ($isCompleted) {
            $this->getLogger()->info('LPA is complete', [
                'lpaid' => $lpa->id
            ]);

            // If we don't already have a complete date...
            if (!($lpa->completedAt instanceof \DateTime)) {
                // And the LPA is locked...
                if ($lpa->locked === true) {
                    $this->getLogger()->info('Setting completed time for existing LPA', [
                        'lpaid' => $lpa->id
                    ]);

                    // Set the date
                    $lpa->completedAt = new DateTime();
                }
            }
        } else {
            $this->getLogger()->info('LPA is not complete', [
                'lpaid' => $lpa->id
            ]);

            $lpa->completedAt = null;
        }

        // If there's a donor, populate the free text search field
        $searchField = null;

        if ($lpa->document->donor != null) {
            $searchField = (string) $lpa->document->donor->name;

            $this->getLogger()->info('Setting search field', [
                'lpaid' => $lpa->id,
                'searchField' => $searchField,
            ]);
        }

        $lastUpdated = new UTCDateTime($lpa->updatedAt);

        $existingLpa = new Lpa();
        $existingLpaResult = $this->lpaCollection->findOne([
            '_id' => $lpa->id
        ]);

        if (!is_null($existingLpaResult)) {
            $existingLpaResult = [
                    'id' => $existingLpaResult['_id']
                ] + $existingLpaResult;

            $existingLpa = new Lpa($existingLpaResult);
        }

        //  Only update the edited date if the LPA document itself has changed
        if (!$lpa->equalsIgnoreMetadata($existingLpa)) {
            // Record the time we updated the document.
            $lpa->updatedAt = new DateTime();

            $this->getLogger()->info('Setting updated time', [
                'lpaid' => $lpa->id,
                'updatedAt' => $lpa->updatedAt,
            ]);
        }

        // updatedAt is included in the query so that data isn't overwritten
        // if the Document has changed since this process loaded it.
        $result = $this->lpaCollection->updateOne(
            ['_id' => $lpa->id, 'updatedAt' => $lastUpdated ],
            ['$set' => array_merge($lpa->toArray(new DateCallback()), ['search' => $searchField])],
            ['upsert' => false, 'multiple' => false]
        );

        // Ensure that one (and only one) document was updated.
        // If not, something when wrong.
        if ($result->getModifiedCount() !== 0 && $result->getModifiedCount() !== 1) {
            throw new RuntimeException('Unable to update LPA. This might be because "updatedAt" has changed.');
        }

        $this->getLogger()->info('LPA updated successfully', [
            'lpaid' => $lpa->id,
            'updatedAt' => $lpa->updatedAt,
        ]);
    }
}
