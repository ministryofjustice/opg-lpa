<?php
namespace Application\Model\Service\System;

use MongoCode;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Generate LPA stats and saves the results back into MongoDB.
 *
 * Class Stats
 * @package Application\Model\Service\System
 */
class Stats implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;


    public function generate(){

        $collection = $this->getServiceLocator()->get('MongoDB-Default-lpa');
        $db = $collection->db;

        // Stats can (ideally) be processed on a secondary.
        $db->setReadPreference( \MongoClient::RP_SECONDARY_PREFERRED );

        //------------------------------------

        // Returns the number of LPAs under each userId

        $map = new MongoCode(
            'function() {
                if( this.user ){
                    emit(this.user,1);
                }
            }'
        );

        $reduce = new MongoCode(
            'function(user, lpas) {
                return lpas.length;
            }'
        );

        $results = $db->command([
            'mapreduce' => $collection->getName(),
            'map' => $map,
            'reduce' => $reduce,
            'out' => ['inline'=>1],
            'query' => [ 'user' => [ '$exists'=>true ] ],
        ])['results'];

        //------------------------------------

        /*
         * This creates an array where:
         *  key = a number or LPAs
         *  value = the number of users with that number of LPAs.
         *
         * This lets us say:
         *  N users have X LPAs
         */

        $byLpaCount = array_reduce(
            $results,
            function( $carry, $item ){

                $count = (int)$item['value'];

                if( !isset($carry[$count]) ){
                    $carry[$count] = 1;
                } else {
                    $carry[$count]++;
                }

                return $carry;
            },
            array()
        );

        //---

        // Sort by key so they're pre-ordered when sent to Mongo.
        ksort($byLpaCount);

        // Map [k]=>v to [k]=>[ _id=>k, count=>v ]
        $mongodata = array_map( function( $k, $v ){
            return [ '_id'=>$k, 'count'=>$v ];
        }, array_keys($byLpaCount), $byLpaCount );

        //---------------------------------------------------
        // Save the results

        $collection = $this->getServiceLocator()->get('MongoDB-Default-stats-lpas');

        // Empty the collection
        $collection->remove( [] );

        // Add the new data
        $result = $collection->batchInsert( $mongodata );

        //---

        return (bool)$result['ok'];

    }

} // class
