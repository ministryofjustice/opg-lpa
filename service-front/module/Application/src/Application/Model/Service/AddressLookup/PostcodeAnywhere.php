<?php
namespace Application\Model\Service\AddressLookup;

use RuntimeException;

use GuzzleHttp\Client as GuzzleClient;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Postcode and address lookup from Postcode Anywhere.
 *
 * Class PostcodeAnywhere
 * @package Application\Model\Service\AddressLookup
 */
class PostcodeAnywhere implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    /**
     * Endpoint for Postcode -> Address lookups (returned as JSON).
     */
    const END_POINT_POSTCODE = 'https://services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/FindByPostcode/v1.00/json3.ws';

    const END_POINT_ADDRESS = 'https://services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/RetrieveById/v1.30/json3.ws';

    //---

    /**
     * Return a list of addresses for a given postcode.
     *
     * @param $postcode string A UK postcode
     * @return array Address list
     */
    public function lookupPostcode( $postcode ){

        $response = $this->client()->get( self::END_POINT_POSTCODE, [
            'query' => [
                'Postcode' => $postcode,
            ]
        ]);

        if( $response->getStatusCode() != 200 ){
            return array();
        }

        $result = $response->json();

        if( !isset($result['Items']) ){
            return array();
        }

        return $result['Items'];

        /**
         * Example response format:

            [0]=> array(3) {
                ["Id"]=>
                string(11) "51749629.00"
                ["StreetAddress"]=>
                string(29) "Apartment 201 8 Walworth Road"
                ["Place"]=>
                string(10) "London SE1"
            }
            [1]=> array(3) {
                ["Id"]=>
                string(11) "51749630.00"
                ["StreetAddress"]=>
                string(29) "Apartment 202 8 Walworth Road"
                ["Place"]=>
                string(10) "London SE1"
            }
         */

    } // function

    public function lookupAddress( $addressId ){

        $response = $this->client()->get( self::END_POINT_ADDRESS, [
            'query' => [
                'Id' => $addressId,
            ]
        ]);

        if( $response->getStatusCode() != 200 ){
            return array();
        }

        $result = $response->json();

        if( !isset($result['Items']) || count($result['Items']) < 1 ){
            return array();
        }

        $address = array_pop($result['Items']);

        //----------------------------------------------
        // Convert address to 3 lines plus a postcode

        // Pull out the relevant keys...
        $components = array_intersect_key( $address, array_flip([ 'Company', 'Line1', 'Line2', 'Line3', 'Line4', 'Line5', 'PostTown', 'County', ]) );

        // Strip out empty values...
        $components = array_values(array_filter($components, function($v){ return !empty($v); }));

        $count = count($components);

        //-----------------------------------------------------------------------
        // Convert address to 3 lines plus a postcode

        # TODO - This could be moved out if we wanted to apply it in other classes.

        // By default assume there is 1 field per line.
        $numOnLine[1] = $numOnLine[2] = $numOnLine[3] = 1;

        // When there are > 3, the fields change...
        if( $count > 3 ){

            /*
             * The number of fields per line becomes dynamic. This populates the lines "bottom up".
             * i.e. Line 2 will always have the same or same + 1 number of fields then line 1.
             * And Line 3 will always have the same or same + 1 number of fields then line 2.
             */

            $numOnLine[1] = floor( $count / 3 );

            $numOnLine[2] = $numOnLine[1];
            if(  ($count % 3) == 2  ) { $numOnLine[2]++; }

            $numOnLine[3] = $numOnLine[2];
            if(  ($count % 3) == 1  ) { $numOnLine[3]++; }

        }

        //--

        $result = array();

        // Populate the 3 lines.
        for( $i = 1; $i <= 3; $i++ ){

            $result["line{$i}"] = '';

            // Pop and appends the number of fields for the line.
            for( $j = 0; $j < $numOnLine[$i]; $j++ ){
                if( !current($components) ){ break; }
                $result["line{$i}"] .= ', ' . trim(array_shift($components));
            }

            $result["line{$i}"] = ltrim( $result["line{$i}"], ', ' );

        } // for

        //---

        $result['postcode'] = $address['Postcode'];

        //---

        /**
         The response is always:
            array(
               'line1' => string
               'line2' => string
               'line3' => string
               'postcode' => string
            )
         */

        return $result;

    } // function

    /**
     * Return a Guzzle Client pre-configured with a Postcode Anywhere Key.
     *
     * @return GuzzleClient
     */
    private function client(){

        $config = $this->getServiceLocator()->get('Config');

        if( !isset( $config['address']['postcodeanywhere']['key'] ) ){
            throw new RuntimeException('No key set for Postcode Anywhere');
        }

        $key = $config['address']['postcodeanywhere']['key'];

        //---

        $client = new GuzzleClient;

        $client->setDefaultOption( 'query/Key', $key );

        return $client;

    } // function

} // class