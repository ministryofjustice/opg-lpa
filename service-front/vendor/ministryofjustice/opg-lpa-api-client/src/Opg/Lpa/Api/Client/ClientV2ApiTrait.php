<?php
namespace Opg\Lpa\Api\Client;

use Opg\Lpa\DataModel\Lpa\Lpa;

use GuzzleHttp\Psr7\Uri;

trait ClientV2ApiTrait {

    //------------------------------------------------------------------------------------
    // Public API access methods

    /**
     * Returns all LPAs for the current user.
     *
     * @param array $query
     * @return array|Exception\ResponseException
     */
    public function getApplicationList( array $query = array() ){

        $applicationList = array();

        $path = sprintf( '/v2/users/%s/applications', $this->getUserId() );

        do {

            $url = new Uri( $this->getApiBaseUri() . $path );

            $response = $this->httpGet( $url, $query );

            if( $response->getStatusCode() != 200 ){
                return new Exception\ResponseException( 'unknown-error', $response->getStatusCode(), $response );
            }

            $body = json_decode($response->getBody(), true);

            if( $body['count'] == 0 ){
                return array();
            }

            if (!isset($body['_links']) || !isset($body['_embedded']['applications'])) {
                return new Exception\ResponseException( 'missing-fields', $response->getStatusCode(), $response );
            }

            foreach ($body['_embedded']['applications'] as $application) {
                $applicationList[] = new Lpa($application);
            }

            if (isset($body['_links']['next']['href'])) {
                $path = $body['_links']['next']['href'];
            } else {
                $path = null;
            }

        } while (!is_null($path));

        //---

        return $applicationList;

    }

    /**
     * Create a new LPA application.
     *
     * @return Response\Lpa|Exception\ResponseException
     */
    public function createApplication(){

        $path = sprintf( '/v2/users/%s/applications', $this->getUserId() );

        $url = new Uri( $this->getApiBaseUri() . $path );

        try {

            $response = $this->httpPost( $url );

            if( $response->getStatusCode() == 201 ){

                return Response\Lpa::buildFromResponse($response);

            }

        } catch ( Exception\ResponseException $e ){
            return $e;
        }

        return new Exception\ResponseException( 'unknown-error', $response->getStatusCode(), $response );

    }

    public function getApplication( $lpaId ){

        $path = sprintf( '/v2/users/%s/applications/%d', $this->getUserId(), $lpaId );

        $url = new Uri( $this->getApiBaseUri() . $path );

        $response = $this->httpGet( $url );

        return ( $response->getStatusCode() == 200 ) ? Response\Lpa::buildFromResponse($response) : false;

    }

    public function updateApplication( $lpaId, Array $data ){

        $path = sprintf( '/v2/users/%s/applications/%d', $this->getUserId(), $lpaId );

        $url = new Uri( $this->getApiBaseUri() . $path );

        $response = $this->httpPatch($url, $data);

        return Response\Lpa::buildFromResponse( $response );

    }

    /**
     * Deletes an LPA application.
     *
     * @param $lpaId
     * @return true|Exception\ResponseException
     */
    public function deleteApplication( $lpaId ){

        $path = sprintf( '/v2/users/%s/applications/%d', $this->getUserId(), $lpaId );

        $url = new Uri( $this->getApiBaseUri() . $path );

        $response = $this->httpDelete( $url );

        if( $response->getStatusCode() == 204 ){
            return true;
        }

        return new Exception\ResponseException( 'unknown-error', $response->getStatusCode(), $response );

    }

    /**
     * Sets the LPA's metadata
     *
     * Setting metadata is a special case as we need to merge client side at present.
     *
     * NB: This is not a deep level merge.
     *
     * @param string $lpaId
     * @param array $metadata
     * @return boolean
     */
    public function setMetaData($lpaId, Array $metadata) {

        $currentMetadata = $this->getMetaData($lpaId);

        if( is_array($currentMetadata) ){

            // Strip out the _links key
            unset( $currentMetadata['_links'] );

            // Merge new data into old
            $metadata = array_merge( $currentMetadata, $metadata );

        }

        //---

        $this->updateApplication($lpaId, [ 'metadata'=>$metadata ]);

        return true;

    }

}
