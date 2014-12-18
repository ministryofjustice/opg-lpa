<?php
namespace Application\Model\Rest\AttorneysReplacement;

use Application\Model\Rest\CollectionInterface;

use Zend\Paginator\Paginator;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Collection extends Paginator implements CollectionInterface {

    protected $lpa;

    public function __construct($adapter, Lpa $lpa){
        parent::__construct( $adapter );

        $this->lpa = $lpa;
    }

    public function userId(){
        return $this->lpa->user;
    }

    public function lpaId(){
        return $this->lpa->id;
    }

    public function resourceId(){
        return null;
    }

    public function toArray(){

        $lpa = $this->lpa;

        $items = iterator_to_array($this->getItemsByPage( $this->getCurrentPageNumber() ));

        // Map the embedded items to Entities...
        $items = array_map( function($i) use( $lpa ){
            return new Entity( $i, $lpa );
        }, $items );

        return [
            'count' => count($items),
            'total' => $this->getTotalItemCount(),
            'pages' => $this->count(),
            'items' => $items,
        ];

    } // function

} // class
