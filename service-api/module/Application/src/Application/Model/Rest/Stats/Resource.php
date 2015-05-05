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
                return new Entity( $this->getAccountsStats() );
                break;
            case 'whoareyou':

                break;
            case 'xxx':

                break;
            default:
                return new ApiProblem( 404, 'Stats type not found.' );

        } // switch

    } // function

    /**
     * Return stats about regarding
     *
     * @return array
     */
    private function getAccountsStats(){

        return array(
            'one' => 1,
            'two' => 2,
            'tree' => 3,
        );

    }

} // cass
