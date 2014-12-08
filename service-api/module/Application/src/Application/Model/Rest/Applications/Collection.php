<?php
namespace Application\Model\Rest\Applications;

use Application\Model\Rest\CollectionInterface;

use Zend\Paginator\Paginator;

use Application\Library\Hal\Hal;

class Collection extends Paginator implements CollectionInterface {

    public function __construct($adapter){
        parent::__construct( $adapter );

        // The number of records per page.
        // Hard code this for now.
        $this->setItemCountPerPage(250);
    }

    public function lpaId(){
        return null;
    }

    public function resourceId(){
        return null;
    }

    /**
     * Returns a page from the Paginator as a Hal object.
     *
     * @param $pageNumber
     * @param callable $routeCallback
     * @return Hal
     */
    public function getHalItemsByPage( $pageNumber, callable $routeCallback ){

        // Return a list of LPA objects for this page...
        $items = $this->getItemsByPage( $pageNumber );

        $hal = new Hal();

        $hal->setData( [ 'count'=>$items->count(), 'total'=>$this->getTotalItemCount(), 'pages'=>$this->count() ] );

        //---

        foreach( $items as $item ){
            $entity = new Entity( $item );
            $entityHal = $entity->getHal( $routeCallback );
            $hal->addResource( 'applications', $entityHal );
        }

        return $hal;

    } // function

} // class
