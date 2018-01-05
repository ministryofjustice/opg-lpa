<?php

namespace Application\Model\Rest;

use Application\DataAccess\Mongo\CollectionFactory;
use Application\DataAccess\Mongo\DateCallback;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\DateTime;
use Application\Library\Lpa\StateChecker;
use Application\Model\Rest\Lock\LockedException;
use Application\Model\Rest\Users\Entity as RouteUser;
use Application\Traits\LogTrait;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use ZfcRbac\Service\AuthorizationServiceAwareInterface;
use ZfcRbac\Service\AuthorizationServiceAwareTrait;
use RuntimeException;

abstract class AbstractResource implements ServiceLocatorAwareInterface, AuthorizationServiceAwareInterface
{
    use LogTrait;

    const TYPE_SINGULAR = 'singular';
    const TYPE_COLLECTION = 'collections';

    //------------------------------------------

    use ServiceLocatorAwareTrait;
    use AuthorizationServiceAwareTrait;

    //------------------------------------------

    /**
     * Resource name
     *
     * @var string
     */
    protected $name;

    /**
     * Resource identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * Resource type
     *
     * @var string
     */
    protected $type;

    /**
     * LPA for this resource
     *
     * @var Lpa
     */
    protected $lpa = null;

    /**
     * User
     *
     * @var RouteUser
     */
    protected $routeUser = null;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getType()
    {
        return $this->type;
    }

    //--------------------------

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

    public function setRouteUser(RouteUser $user)
    {
        $this->routeUser = $user;
    }

    /**
     * @return RouteUser
     */
    public function getRouteUser()
    {
        if (!$this->routeUser instanceof RouteUser) {
            throw new RuntimeException('Route User not set');
        }

        return $this->routeUser;
    }

    public function checkAccess($userId = null)
    {
        if (is_null($userId) && $this->getRouteUser() != null) {
            $userId = $this->getRouteUser()->userId();

            $this->info('Access allowed for user', ['userid' => $userId]);
        }

        if (!$this->getAuthorizationService()->isGranted('authenticated')) {
            throw new UnauthorizedException('You need to be authenticated to access this resource');
        }

        if (!$this->getAuthorizationService()->isGranted('isAuthorizedToManageUser', $userId)
            && !$this->getAuthorizationService()->isGranted('admin')) {
            throw new UnauthorizedException('You do not have permission to access this resource');
        }
    }

    //------------------------------------------

    /**
     * @param $collection string Name of the requested collection.
     * @return Collection
     */
    public function getCollection( $collection ){
        return $this->getServiceLocator()->get( CollectionFactory::class . "-{$collection}" );
    }

    //------------------------------------------

    /**
     * Helper method for saving an updated LPA.
     *
     * @param Lpa $lpa
     */
    protected function updateLpa( Lpa $lpa ){

        $this->info('Updating LPA', ['lpaid' => $lpa->id]);

        // Should already have been checked, but no harm checking again.
        $this->checkAccess();

        //--------------------------------------------------------

        // Check LPA is (still) valid.
        if( $lpa->validateForApi()->hasErrors() ){
            throw new RuntimeException('LPA object is invalid');
        }

        //--------------------------------------------------------

        $collection = $this->getCollection('lpa');

        //--------------------------------------------------------
        // Check LPA in database isn't locked...

        $locked = $collection->count( [ '_id'=>$lpa->id, 'locked'=>true ], [ '_id'=>true ] ) > 0;

        if( $locked === true ){
            throw new LockedException('LPA has already been locked.');
        }

        //--------------------------------------------------------
        // If instrument created, record the date.

        $isCreated = (new StateChecker($lpa))->isStateCreated();

        if( $isCreated ){

            $this->info('LPA is created', ['lpaid' => $lpa->id]);

            if( !($lpa->createdAt instanceof \DateTime) ){

                $this->info('Setting created time for existing LPA', ['lpaid' => $lpa->id]);

                $lpa->createdAt = new DateTime();
            }

        } else {

            $this->info('LPA is not fully created', ['lpaid' => $lpa->id]);

            $lpa->createdAt = null;
        }

        //--------------------------------------------------------
        // If completed, record the date.

        $isCompleted = (new StateChecker($lpa))->isStateCompleted();

        if( $isCompleted ){

            $this->info('LPA is complete', ['lpaid' => $lpa->id]);

            // If we don't already have a complete date...
            if( !($lpa->completedAt instanceof \DateTime) ){
                $this->info('Setting completed time for existing LPA', ['lpaid' => $lpa->id]);

                // And the LPA is locked...
                if( $lpa->locked === true ){

                    // Set teh date.
                    $lpa->completedAt = new DateTime();

                }

            }

        } else {

            $this->info('LPA is not complete', ['lpaid' => $lpa->id]);

            $lpa->completedAt = null;
        }

        //--------------------------------------------------------
        // If there's a donor, populate the free text search field

        $searchField = null;

        if( $lpa->document->donor != null ){

            $searchField = (string)$lpa->document->donor->name;

            $this->info('Setting search field', [
                    'lpaid' => $lpa->id,
                    'searchField' => $searchField,
                ]
            );
        }

        //--------------------------------------------------------

        $lastUpdated = new UTCDateTime($lpa->updatedAt);

        $existingLpa = new Lpa();
        $existingLpaResult = $collection->findOne( [ '_id'=>$lpa->id ] );
        if( !is_null($existingLpaResult) ){
            $existingLpaResult = [ 'id' => $existingLpaResult['_id'] ] + $existingLpaResult;
            $existingLpa = new Lpa( $existingLpaResult );
        }

        //Only update the edited date if the LPA document itself has changed
        if(!$lpa->equalsIgnoreMetadata($existingLpa)) {
            // Record the time we updated the document.
            $lpa->updatedAt = new DateTime();

            $this->info('Setting updated time', [
                    'lpaid' => $lpa->id,
                    'updatedAt' => $lpa->updatedAt,
                ]
            );
        }

        // updatedAt is included in the query so that data isn't overwritten
        // if the Document has changed since this process loaded it.
        $result = $collection->updateOne(
            [ '_id'=>$lpa->id, 'updatedAt'=>$lastUpdated ],
            ['$set' => array_merge($lpa->toArray(new DateCallback()), ['search' => $searchField])],
            [ 'upsert'=>false, 'multiple'=>false ]
        );

        // Ensure that one (and only one) document was updated.
        // If not, something when wrong.
        if( $result->getModifiedCount() !== 0 && $result->getModifiedCount() !== 1 ){
            throw new RuntimeException('Unable to update LPA. This might be because "updatedAt" has changed.');
        }

        $this->info('LPA updated successfully', [
               'lpaid' => $lpa->id,
               'updatedAt' => $lpa->updatedAt,
            ]
        );

    } // function

} // abstract class
