<?php

namespace Application\Model\Rest\Applications;

use Application\Model\Rest\CollectionInterface;
use Zend\Paginator\Paginator;

class Collection extends Paginator implements CollectionInterface {

    protected $userId;

    public function __construct( $adapter, $userId ){
        parent::__construct( $adapter );

        $this->userId = $userId;

        // The number of records per page.
        // Hard code this for now.
        $this->setItemCountPerPage(250);
    }

    public function userId(){
        return $this->userId;
    }

    public function lpaId(){
        return null;
    }

    public function resourceId(){
        return null;
    }

    public function toArray(){

        $items = iterator_to_array($this->getItemsByPage( $this->getCurrentPageNumber() ));

        // Map the embedded items to Entities...
        $items = array_map( function($i){
            return new AbbreviatedEntity( $i );
        }, $items );

        return [
            'count' => count($items),
            'total' => $this->getTotalItemCount(),
            'pages' => $this->count(),
            'items' => $items,
        ];

    } // function

} // class
