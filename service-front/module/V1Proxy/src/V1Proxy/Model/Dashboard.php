<?php
namespace V1Proxy\Model;

use Zend\Session\Container;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Dashboard implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    /**
     * Namespace used to cache the fact that
     * a given user has no v1 LPAs in their account.
     */
    const USER_HAS_NO_V1_LPAS = 'no-lpas:';

    //---


    public function deleteAllLpasAndAccount(){

        $lpas = $this->getLpas( false );

        //---

        $client = $this->getServiceLocator()->get('ProxyOldApiClient');

        // Delete each LPA...
        foreach( $lpas as $lpa ){

            // Zero-pad the id...
            $id = sprintf("%010d", $lpa->id);

            $client->delete( "http://api.local/applications/{$id}" );

        }

        //----------------------------------------------------
        // Delete the user form the account server.

        // Get the user's email address...
        $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');
        $emailAddress = (string)$detailsContainer->user->email;


        // Find their account service ID...
        $response = $client->get( "http://accounts.local/query?email=".$emailAddress );
        $response = $response->json();

        // If we have the id...
        if( isset($response['id']) ){

            $client->delete( "http://accounts.local/account/".$response['id'] );

        }

        return true;

    } // function

    public function searchLpas( $query ){
        return $this->getLpas( false, $query );
    }

    /**
     * Return a list of laps
     *
     * @return array|mixed
     */
    public function getLpas( $useCache = true, $query = null ){

        $config = $this->getServiceLocator()->get('Config')['v1proxy'];

        $session = new Container('V1ProxyLpaCache');

        //--------------------------------------------------------------
        // Check if we've cached that the user has no v1 LPAs

        // If we've cached that the user has no v1 LPAs...
        if( $useCache && $config['cache-no-lpas'] && isset($session->noLpas) && $session->noLpas === true ){
            return array();
        }

        //--------------------------------------------------------------
        // Return a list of LPAs from v1 API

        $client = $this->getServiceLocator()->get('ProxyOldApiClient');

        if( isset($query) ){

            $response = $client->get( 'http://api.local/summary/search', [
                'query' => [ 'freeText' => $query ]
            ]);

        } else {

            $response = $client->get( 'http://api.local/summary' );

        }

        if( $response->getStatusCode() != 200 ){
            throw new \RuntimeException( 'Error accessing v1 API' );
        }

        //---

        $xml = $response->xml();
        
        $json = json_encode( $xml );
        
        $array = json_decode($json,TRUE);

        if( !isset($array['lpa']) || count($array['lpa']) == 0 ){

            // Cache the lack of a result...
            if( $config['cache-no-lpas'] && is_null($query) ){
                $session->noLpas = true;
            }

            return array();
        }

        //--------------------------------------------------------------
        // Map the returned LPAs to a standard data structure

        $result = array();

        foreach( $array['lpa'] as $k=>$lpa ){
            
            // Don't include registration sections.
            if( $k === 'registration' ){
                continue;
            }

            if( isset($lpa['application']) ){
                // If there is more than one LPA, then there will be an application section.
                $application = $lpa['application'];
            } else {
                // Otherwise if there is only 1 LPA, then $lpa is already teh application.
                $application = $lpa;
            }

            $href = $application['@attributes']['href'];

            //---

            $obj = new \stdClass();

            $obj->id = (int)array_pop( explode( '/', $href ) );

            $obj->version = 1;

            $obj->donor = ( isset($application['donor-name']) && is_string($application['donor-name']) ) ? $application['donor-name'] : null;

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
                    $obj->status = 'Completed';
                }

            } // if

            //--------------------------------------------

            if( !is_null($obj->updatedAt ) ){
                $obj->updatedAt  = new \DateTime( $obj->updatedAt, new \DateTimeZone('UTC')  );
            }

            //---

            $result[] = $obj;

        } // foreach

        //---

        return $result;
    }

    /**
     * Clears any cached LPA details for the current user.
     * This should be called when any amends are made to any v1 LPA.
     */
    public function clearLpaCacheForUser(){

        // As we no longer cache, this method isn't needed.
        // It's left however to avoid removing calls to it.
        // (they will all go when v1 is thrown away)

    }

} // class
