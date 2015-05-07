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

            case 'lpasperuser':
                return new Entity( $this->getLpasPerUser() );

            default:
                return new ApiProblem( 404, 'Stats type not found.' );

        } // switch

    } // function

    /**
     * Return general stats on LPA numbers.
     *
     * Some of this could be done using aggregate queries, however I'd rather keep the queries simple.
     * Stats are not looked at very often, so performance when done like this should be "good enough".
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

        //---

        return array(
            'all' => $summary,
            'health-and-welfare' => $hw,
            'property-and-finance' => $pf,
            'by-month' => $byMonth
        );

    } // function
    
    /**
     * Returns a list of lpa counts and user counts, in order to
     * answer questions of the form how many users have five LPAs?
     *
     * @return array
     * 
     * The key of the return array is the number of LPAs
     * The value is the number of users with this many LPAs
     */
    private function getLpasPerUser(){
        
        $collection = $this->getCollection('lpa');
        
        // Stats can (ideally) be processed on a secondary.
        $collection->setReadPreference( \MongoClient::RP_SECONDARY_PREFERRED );
        
        // Get a list of users and the number of applications they have
        $results = $collection->group(
            ['user' => 1],
            ['lpacount' => 0],
            'function (obj, prev) { prev.lpacount ++; }'
        )['retval'];
        
        // Create an array indexed by userId with lpaCount as a value
        $userLpaCounts = [];
        foreach ($results as $result) {
            
            $userId = $result['user'];
            $lpaCount = $result['lpacount'];
            
            if (!empty($userId)) {
                $userLpaCounts[$userId] = $lpaCount;
            }
        }

        // Create an array indexed by number of lpas with number of users as the value
        $byLpaCount = [];
        foreach ($userLpaCounts as $userId => $lpaCount) {
            if (isset($byLpaCount[$lpaCount])) {
                $byLpaCount[$lpaCount] ++;
            } else {
                $byLpaCount[$lpaCount] = 1;
            }
        }

        // Create an array indexed by number of users with number of lpas as the value
        $byUserCount = [];
        foreach ($byLpaCount as $lpaCount => $userCount) {
            if (isset($byUserCount[$userCount])) {
                $byUserCount[$userCount] += $lpaCount;
            } else {
                $byUserCount[$userCount] = $lpaCount;
            }
        }
        
        krsort($byLpaCount);
        krsort($byUserCount);
        
        return [
            'byLpaCount' => $byLpaCount,
            'byUserCount' => $byUserCount,  
        ];
    } // function getLpasPerUser()
        
} // class
