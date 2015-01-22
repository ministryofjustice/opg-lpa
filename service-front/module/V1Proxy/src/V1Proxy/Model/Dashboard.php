<?php
namespace V1Proxy\Model;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Dashboard implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    public function getLpas(){

        $cache = $this->getServiceLocator()->get('ProxyCache');
        // TODO - Check the cache

        //-----

        $client = $this->getServiceLocator()->get('ProxyOldApiClient');

        $response = $client->get( 'http://api.local/summary' );

        if( $response->getStatusCode() != 200 ){
            throw new \RuntimeException( 'Error accessing v1 API' );
        }

        //---

        $xml = $response->xml();
        $json = json_encode( $xml );
        $array = json_decode($json,TRUE);

        if( !isset($array['lpa']) || count($array['lpa']) == 0 ){

            # Record that the user has no old LPAs.
            # TODO - Stored fact in cache.

            return array();
        }

        //---

        $result = array();

        foreach( $array['lpa'] as $lpa ){

            $application = $lpa['application'];

            //---

            $obj = new \stdClass();

            $obj->donor = ( isset($application['donor-name']) ) ? $application['donor-name'] : null;

            //---

            $obj->type = null;

            if( isset($application['@attributes']['type']) ){

                if( $application['@attributes']['type'] == 'application/vnd.opg.lpa.application.property-finance+xml' ){
                    $obj->type = 'property-and-financial';
                } else {
                    $obj->type = 'health-and-welfare';
                }

            }

            //---

            $obj->updatedAt = ( isset($application['last-modified']) ) ? $application['last-modified'] : null;

            $obj->status = 'Started';

            //--------------------------------------------

            if( isset($lpa['registration']) ){

                $registration = $lpa['registration'];

                $updated = ( isset($registration['last-modified']) ) ? $registration['last-modified'] : null;

                // Use the latest updated at date.
                $obj->updatedAt = max( $obj->updatedAt, $updated );


                //---

                // If here, it's created.
                $obj->status = 'Created';

                if( isset($registration['is-signed']) && $registration['is-signed'] == 'yes' ){
                    $obj->status = 'Signed';
                }

                if( isset($registration['is-complete']) && $registration['is-complete'] == 'yes' ){
                    $obj->status = 'Complete';
                }

            } // if

            //--------------------------------------------

            if( !is_null($obj->updatedAt ) ){
                $obj->updatedAt  = new \DateTime( $obj->updatedAt, new \DateTimeZone('UTC')  );
            }

            //---

            $result[] = $obj;

        }

        //---

        # TODO - cache $result

        //---

        return $result;
    }

} // class
