<?php
namespace Application\Model\Rest\Stats;

use MongoDate;

use Opg\Lpa\DataModel\Lpa\Document\Document;

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
     * Return general stats on LPA numbers.
     *
     * @return array
     */
    private function getLpaStats(){

        $collection = $this->getCollection('lpa');

        // Stats can (ideally) be processed on a secondary.
        $collection->setReadPreference( \MongoClient::RP_SECONDARY_PREFERRED );

        
        //-----------------------------
        // Broken down by month

        $byMonth = array();

        $start = new \DateTime();
        $start->setTime(0, 0, 0)->modify('next month'); // Seed one month ahead

        $end = new \DateTime();
        $end->setTime(23, 59, 59)->modify('next month'); // Seed one month ahead

        //---

        // Go back 4 months...
        for( $i = 1; $i <=4; $i++ ){

            // Go back 1 (more) month...
            $from = new MongoDate( $start->modify("first day of -1 month")->getTimestamp() );
            $to =   new MongoDate( $end->modify("last day of -1 month")->getTimestamp() );

            $month = array();

            // Started if we have a startedAt, but no createdAt...
            $month['started'] = $collection->find([
                'startedAt' => [ '$gte' => $from, '$lte' => $to ],
            ])->count();

            // Created if we have a createdAt, but no completedAt...
            $month['created'] = $collection->find([
                'createdAt' => [ '$gte' => $from, '$lte' => $to ],
            ])->count();

            // Count all the LPAs that have a completedAt...
            $month['completed'] = $collection->find([
                'completedAt' => [ '$gte' => $from, '$lte' => $to ],
            ])->count();

            //---

            $byMonth[date('Y-m',$start->getTimestamp())] = $month;

        } // for

        //-----------------------------

        $summary = array();

        //--------------------
        // Broken down by type

        $pf = array();

        // Started if we have a startedAt, but no createdAt...
        $summary['started'] = $pf['started'] = $collection->find([
            'startedAt' => [ '$ne' => null ],
            'createdAt' => null,
            'document.type' => Document::LPA_TYPE_PF
        ])->count();

        // Created if we have a createdAt, but no completedAt...
        $summary['created'] = $pf['created'] = $collection->find([
            'createdAt' => [ '$ne' => null ],
            'completedAt' => null,
            'document.type' => Document::LPA_TYPE_PF
        ])->count();

        // Count all the LPAs that have a completedAt...
        $summary['completed'] = $pf['completed'] = $collection->find([
            'completedAt' => [ '$ne' => null ],
            'document.type' => Document::LPA_TYPE_PF
        ])->count();

        //---

        $hw = array();

        // Started if we have a startedAt, but no createdAt...
        $summary['started'] += $hw['started'] = $collection->find([
            'startedAt' => [ '$ne' => null ],
            'createdAt' => null,
            'document.type' => Document::LPA_TYPE_HW
        ])->count();

        // Created if we have a createdAt, but no completedAt...
        $summary['created'] += $hw['created'] = $collection->find([
            'createdAt' => [ '$ne' => null ],
            'completedAt' => null,
            'document.type' => Document::LPA_TYPE_HW
        ])->count();

        // Count all the LPAs that have a completedAt...
        $summary['completed'] += $hw['completed'] = $collection->find([
            'completedAt' => [ '$ne' => null ],
            'document.type' => Document::LPA_TYPE_HW
        ])->count();

        //--------------------

        // Deleted LPAs have no 'document'...
        $summary['deleted'] = $collection->find( [ 'document' => [ '$exists' => false ] ] )->count();

        $summary['total'] = $summary['completed'] + $summary['created'] + $summary['started'] + $summary['deleted'];

        //---

        return array(
            'summary' => $summary,
            'health-and-welfare' => $hw,
            'property-and-finance' => $pf,
            'by-month' => $byMonth
        );

    } // function

} // cass
