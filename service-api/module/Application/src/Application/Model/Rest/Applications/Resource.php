<?php
namespace Application\Model\Rest\Applications;

use Application\Library\DateTime;

use Application\Model\Rest\AbstractResource;

use Application\Library\Random\Csprng;

use ZfcRbac\Exception\UnauthorizedException;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document;

/**
 * Application Resource
 *
 * Class Resource
 * @package Application\Model\Rest\Applications
 */
class Resource extends AbstractResource {


    public function getName(){
        return 'applications';
    }

    /**
     * Create a new LAP.
     *
     * @param  mixed $data
     * @return Entity|Error
     * @ throw UnauthorizedException If the current user is not authorized.
     */
    public function create($data){

        if (!$this->getAuthorizationService()->isGranted('create-lpa')) {
            throw new UnauthorizedException('Cannot create.');
        }

        if ( !$this->getAuthorizationService()->isGranted('isAuthorizedToManageUser', $this->getRouteUser()) ) {
            throw new UnauthorizedException('You cannot create LPAs for other users');
        }

        //------------------------

        $document = new Document\Document( $data );

        $valid = $document->validate();

        if( $valid->hasErrors() ){
            // Deal with them!
            die('has errors');
        }

        //----------------------------
        // Generate an id for the LPA

        $collection = $this->getCollection('lpa');

        $csprng = new Csprng();

        do {

            $id = $csprng->GetInt(1, 99999999999);

            $exists = $collection->findOne( [ '_id'=>$id ], [ '_id'=>true ] );

        } while( !is_null($exists) );

        //----------------------------

        $lpa = new Lpa([
            'id'                => $id,
            'createdAt'         => new DateTime(),
            'updatedAt'         => new DateTime(),
            'user'              => $this->getRouteUser(),
            'locked'            => false,
            'whoAreYouAnswered' => false,
            'document'          => $document,
        ]);

        //---

        $valid = $lpa->validate();

        if( $valid->hasErrors() ){
            // Deal with them!
            die('has errors');
        }

        $lpaArray = $lpa->toMongoArray();

        //$result = $collection->insert( $lpaArray );

        $entity = new Entity( $lpa );

        return $entity;


        // Newly to return the newly created times...

        // The full document...
        // It's id, to go in the route.

        // Needs to return the entity....


    } // class

} // class
