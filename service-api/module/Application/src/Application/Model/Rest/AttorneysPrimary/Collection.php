<?php
namespace Application\Model\Rest\AttorneysPrimary;

use Application\Model\Rest\CollectionInterface;

use Zend\Paginator\Paginator;

use Opg\Lpa\DataModel\Lpa\Lpa;

use Application\Library\Hal\Hal;

class Collection extends Paginator implements CollectionInterface {

    protected $lpa;

    public function __construct($adapter, Lpa $lpa){
        parent::__construct( $adapter );

        $this->lpa = $lpa;
    }

    public function lpaId(){
        return $this->lpa->id;
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
            $entity = new Entity( $item, $this->lpa );
            $entityHal = $entity->getHal( $routeCallback );
            $hal->addResource( 'applications', $entityHal );
        }

        return $hal;

    } // function

} // class
