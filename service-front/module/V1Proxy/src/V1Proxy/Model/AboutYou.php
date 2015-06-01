<?php
namespace V1Proxy\Model;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Application\Model\Service\ServiceDataInputInterface;

/**
 * This class us used to 'import' About You details from v1 into v2.
 *
 * Class AboutYou
 * @package V1Proxy\Model
 */
class AboutYou implements ServiceLocatorAwareInterface, ServiceDataInputInterface {

    use ServiceLocatorAwareTrait;

    private $details = array();

    /**
     * Checks for and load a user's v1 details.
     *
     * Returns true if we get the details and they contain at least a name.
     *
     * @param $emailAddress
     * @return bool
     */
    public function hasValidDetails( $emailAddress ){

        $client = $this->getServiceLocator()->get('ProxyOldApiClient');

        $response = $client->get( "https://accountv1-01/query?email=".$emailAddress );
        $response = $response->json();

        if( !isset($response['id']) ){
            return false;
        }

        $response = $client->get( "https://accountv1-01/account/".$response['id'] );

        $this->details = $details = $response->json();

        //---

        // Ad a minimum we need a name.
        if( isset( $details['name'] ) ){

            $name = $details['name'];

            if( isset($name['title']) && isset($name['first']) && isset($name['last']) ){
                return true;
            }

        }

        return false;

    } // class

    /**
     * Returns the v1 details, formatted for v2.
     *
     * @return array
     */
    public function getDataForModel(){

        $response = array();

        $response['name-title'] = $this->details['name']['title'];
        $response['name-first'] = $this->details['name']['first'];
        $response['name-last'] = $this->details['name']['last'];

        // If we have id, add their DOB.
        if( isset($this->details['dob']) && is_numeric($this->details['dob']) && strlen($this->details['dob']) == 8 ){

            $response['dob-date'] = substr($this->details['dob'], 0, 4).'-'.substr($this->details['dob'], 4, 2).'-'.substr($this->details['dob'], 6);

        }

        // If we have id, add their address.
        if( isset($this->details['address']) && is_array($this->details['address']) && !empty($this->details['address']) ) {

            $response['address-postcode'] = $this->details['address']['postcode'];

            // Don't include this in the below.
            unset($this->details['address']['postcode']);

            // We don't use this any more.
            unset($this->details['address']['country']);

            //-----------------------------------------------------------------------
            // Convert address to 3 lines plus a postcode

            // Strip out empty values...
            $components = array_values(array_filter($this->details['address'], function ($v) {
                return !empty($v);
            }));

            $count = count($components);

            // By default assume there is 1 field per line.
            $numOnLine[1] = $numOnLine[2] = $numOnLine[3] = 1;

            // When there are > 3, the fields change...
            if ($count > 3) {

                /*
                 * The number of fields per line becomes dynamic. This populates the lines "bottom up".
                 * i.e. Line 2 will always have the same or same + 1 number of fields then line 1.
                 * And Line 3 will always have the same or same + 1 number of fields then line 2.
                 */

                $numOnLine[1] = floor($count / 3);

                $numOnLine[2] = $numOnLine[1];
                if (($count % 3) == 2) {
                    $numOnLine[2]++;
                }

                $numOnLine[3] = $numOnLine[2];
                if (($count % 3) == 1) {
                    $numOnLine[3]++;
                }

            }

            //--

            $result = array();

            // Populate the 3 lines.
            for ($i = 1; $i <= 3; $i++) {

                $result["line{$i}"] = '';

                // Pop and appends the number of fields for the line.
                for ($j = 0; $j < $numOnLine[$i]; $j++) {
                    if (!current($components)) {
                        break;
                    }
                    $result["line{$i}"] .= ', ' . trim(array_shift($components));
                }

                $result["line{$i}"] = ltrim($result["line{$i}"], ', ');

            } // for

            //-----------------------------------------------------------------------

            $response['address-address1'] = ( !empty($result['line1']) ) ? $result['line1'] : null;
            $response['address-address2'] = ( !empty($result['line2']) ) ? $result['line2'] : null;
            $response['address-address3'] = ( !empty($result['line3']) ) ? $result['line3'] : null;


        } // if address

        return $response;

    } // function

} // class
