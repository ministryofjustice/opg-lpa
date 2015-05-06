<?php
namespace Application\Model\Rest\Stats;

use Application\Model\Rest\AbstractResource;

use Application\Library\ApiProblem\ApiProblem;

class Resource extends AbstractResource {

    public function getIdentifier(){ return 'type'; }
    public function getName(){ return 'stats'; }

    public function getType(){
        return self::TYPE_COLLECTION;
    }

    public function fetch( $type ){

        switch( $type ){
            case 'lpas':
                return new Entity( $this->getLpaStats() );

            case 'whoareyou':
                return new Entity( array() );

            case 'xxx':
                return new Entity( array() );

            default:
                return new ApiProblem( 404, 'Stats type not found.' );

        } // switch

    } // function

    /**
     * Return stats about regarding
     *
     * @return array
     */
    private function getLpaStats(){

        return array(
            'one' => 1,
            'two' => 2,
            'tree' => 3,
        );

    }

} // cass
