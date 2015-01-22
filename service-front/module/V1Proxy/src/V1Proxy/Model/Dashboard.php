<?php
namespace V1Proxy\Model;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Dashboard implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    /**
     * Namespace used to cache the fact that
     * a given user has no v1 LPAs in their account.
     */
    const USER_HAS_NO_V1_LAPS = 'no-laps:';

    /**
     * Namespace used to cache a returned list
     * of v1 LPAs for a given user.
     */
    const CACHED_V1_LAPS = 'laps:';


    public function getLpas(){

        $hashedUserId = $this->getHashedUserId();

        //--------------------------------------------------------------
        // Check if we've cached that the user has no v1 LPAs

        $cache = $this->getServiceLocator()->get('ProxyCache');

        // If we've cached that the user has no v1 LPAs...
        if( (bool)$cache->getItem( self::USER_HAS_NO_V1_LAPS . $hashedUserId ) === true ){
            return array();
        }

        //--------------------------------------------------------------
        // Check if we've cached a list of v1 LPAs

        $result = $cache->getItem( self::CACHED_V1_LAPS . $hashedUserId );

        if( is_string($result) ){
            return unserialize( $result );
        }

        //--------------------------------------------------------------
        // Return a list of LPAs from v1 API

        $client = $this->getServiceLocator()->get('ProxyOldApiClient');

        $response = $client->get( 'http://api.local/summary' );

        if( $response->getStatusCode() != 200 ){
            throw new \RuntimeException( 'Error accessing v1 API' );
        }

        //---

        $xml = $response->xml();
        $json = json_encode( $xml );
        $array = json_decode($json,TRUE);

        // If no LPAs were found, cache that fact...
        if( !isset($array['lpa']) || count($array['lpa']) == 0 ){
            $cache->setItem( self::USER_HAS_NO_V1_LAPS . $hashedUserId, true );
            return array();
        }

        //--------------------------------------------------------------
        // Map the returned LPAs to a standard data structure

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

        } // foreach

        //--------------------------------------------------------------
        // Cache the generated list of v1 LPAs

        // Amend the TTL to 24 hours for this...
        $cache->setOptions( $cache->getOptions()->setTtl( 60 * 60 * 24 ) );

        // Cache the results...
        $cache->setItem( self::CACHED_V1_LAPS . $hashedUserId, serialize( $result ) );

        //----------------

        return $result;
    }

    public function clearLpaCacheForUser(){

        $hashedUserId = $this->getHashedUserId();

        $cache = $this->getServiceLocator()->get('ProxyCache');

        $cache->removeItem( self::CACHED_V1_LAPS . $hashedUserId );

    }

    private function getHashedUserId(){

        $auth = $this->getServiceLocator()->get('AuthenticationService');

        if (!$auth->hasIdentity()) { throw new \RuntimeException('V1Proxy Authentication error: no Identity'); }

        return hash( 'sha256', $auth->getIdentity()->id() );

    }

} // class
