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

            $client->delete( "https://apiv1-01/applications/{$id}" );

        }

        //----------------------------------------------------
        // Delete the user form the account server.

        // Get the user's email address...
        $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');
        $emailAddress = (string)$detailsContainer->user->email;


        // Find their account service ID...
        $response = $client->get( "https://accountv1-01/query?email=".$emailAddress );
        $response = $response->json();

        // If we have the id...
        if( isset($response['id']) ){

            $client->delete( "https://accountv1-01/account/".$response['id'] );

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

        $hashedUserId = $this->getHashedUserId();

        $session = $this->getSession();

        //--------------------------------------------------------------
        // Check if we've cached that the user has no v1 LPAs

        $cache = $this->getServiceLocator()->get('ProxyCache');

        // If we've cached that the user has no v1 LPAs...
        if( $useCache && (bool)$cache->getItem( self::USER_HAS_NO_V1_LPAS . $hashedUserId ) === true ){
            return array();
        }

        //--------------------------------------------------------------
        // Check if we've cached a list of v1 LPAs

        if( $useCache && isset($session->lpas) && is_array($session->lpas) ){
            return $session->lpas;
        }

        //--------------------------------------------------------------
        // Return a list of LPAs from v1 API

        $client = $this->getServiceLocator()->get('ProxyOldApiClient');

        if( isset($query) ){

            $response = $client->get( 'https://apiv1-01/summary/search', [
                'query' => [ 'freeText' => $query ]
            ]);

        } else {

            $response = $client->get( 'https://apiv1-01/summary' );

        }

        if( $response->getStatusCode() != 200 ){
            throw new \RuntimeException( 'Error accessing v1 API' );
        }

        //---

        $xml = $response->xml();
        $json = json_encode( $xml );
        $array = json_decode($json,TRUE);

        // If no LPAs were found, cache that fact...
        if( !isset($array['lpa']) || count($array['lpa']) == 0 ){

            if( $config['cache-no-lpas'] ){
                $cache->setItem( self::USER_HAS_NO_V1_LPAS . $hashedUserId, true );
            }

            return array();
        }

        //--------------------------------------------------------------
        // Map the returned LPAs to a standard data structure

        foreach( $array['lpa'] as $lpa ){

            if( isset($lpa['application']) ){
                $application = $lpa['application'];
            } else {
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

        $session->lpas = $result;

        //----------------

        return $result;
    }

    /**
     * Clears any cached LPA details for the current user.
     * This should be called when any amends are made to any v1 LPA.
     */
    public function clearLpaCacheForUser(){

        $session = $this->getSession();

        unset($session->lpas);

    }

    private function getSession(){
        return new Container('V1ProxyLpaCache');
    }

    /**
     * Conceal user ids in the cache by using a hash.
     *
     * @return string
     */
    private function getHashedUserId(){

        $auth = $this->getServiceLocator()->get('AuthenticationService');

        if (!$auth->hasIdentity()) { throw new \RuntimeException('V1Proxy Authentication error: no Identity'); }

        return hash( 'sha256', $auth->getIdentity()->id() );

    }

} // class
