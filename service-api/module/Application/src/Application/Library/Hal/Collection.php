<?php
namespace Application\Library\Hal;

use RuntimeException;

use Application\Model\Rest\EntityInterface;
use Application\Model\Rest\CollectionInterface;

class Collection extends Hal {

    protected $collection;
    protected $collectionName;

    private $linksSet = false;


    public function __construct( CollectionInterface $collection, $collectionName ){
        $this->collectionName = $collectionName;
        $this->setCollection($collection);
    }

    public function setCollection( CollectionInterface $collection  ){

        $this->linksSet = false;
        $this->collection = $collection;

        //---

        $data = $collection->toArray();

        //---

        // Add the resources...

        foreach( $data['items'] as $item ){
            $this->addResource( $this->collectionName, new Entity( $item ) );
        }

        unset( $data['items'] );

        //---

        $this->setData( $data );

    } // function

    //---

    public function getLinks(){

        if( !$this->linksSet ){ throw new RuntimeException( 'Cannot return links until they have been set.' ); }

        return parent::getLinks();

    }

    //---

    public function setLinks( callable $routeCallback ){

        $currentPage = $this->collection->getCurrentPageNumber();

        //-------------------------------------------------------------
        // First

        $this->addLink( 'first', call_user_func($routeCallback, 'api-v1/user/level-1', $this->collection) );

        //-------------------------------------------------------------
        // Self

        if( $currentPage == 1 ){

            $this->addLink( 'self', call_user_func($routeCallback, 'api-v1/user/level-1', $this->collection) );

        } else {

            $this->addLink( 'self', call_user_func(
                $routeCallback, 'api-v1/user/level-1',
                $this->collection,
                [ 'page' => $currentPage ]
            ));

        } // if

        //-------------------------------------------------------------
        // Previous

        if ($currentPage - 1 > 0) {

            if ($currentPage - 1 == 1) {

                $this->addLink( 'prev', call_user_func($routeCallback, 'api-v1/user/level-1', $this->collection) );

            } else {

                $this->addLink( 'prev', call_user_func(
                    $routeCallback, 'api-v1/user/level-1',
                    $this->collection,
                    [ 'page' => $currentPage - 1 ]
                ));

            }

        } // if

        //-------------------------------------------------------------
        // Next

        // 'next'...
        if ($currentPage + 1 <= $this->collection->count()) {

            $this->addLink( 'next', call_user_func(
                $routeCallback, 'api-v1/user/level-1',
                $this->collection,
                [ 'page' => $currentPage + 1 ]
            ));

        } // if

        //-------------------------------------------------------------
        // Last

        if( $this->collection->count() <= 1 ){

            $this->addLink( 'last', call_user_func($routeCallback, 'api-v1/user/level-1', $this->collection) );

        } else {

            $this->addLink( 'last', call_user_func(
                $routeCallback, 'api-v1/user/level-1',
                $this->collection,
                [ 'page' => $this->collection->count() ]
            ));

        }

        //-------------------------------------------------------------
        // Add Links for resources...

        $resources = $this->getResources();

        if( isset($resources[ $this->collectionName ]) ){
            foreach( $resources[ $this->collectionName ] as $resource ){
                $resource->setLinks( $routeCallback );
            }
        }

        //-------------------------------------------------------------

        $this->linksSet = true;

    } // function

} // class
