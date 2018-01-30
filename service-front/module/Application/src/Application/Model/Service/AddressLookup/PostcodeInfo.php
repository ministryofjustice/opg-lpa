<?php

namespace Application\Model\Service\AddressLookup;

use MinistryOfJustice;
use MinistryOfJustice\PostcodeInfo\Response\Address;

/**
 * Postcode and address lookup from Postcode Anywhere.
 *
 * Class PostcodeInfo
 * @package Application\Model\Service\AddressLookup
 */
class PostcodeInfo
{
    /**
     * Return a list of addresses for a given postcode.
     *
     * @param $postcode string A UK postcode
     * @return array Address list
     */
    public function lookupPostcode( $postcode )
    {

        $postcodeInfoClient = $this->getServiceLocator()->get('PostcodeInfoClient');
        
        $addresses = $postcodeInfoClient->lookupPostcodeAddresses($postcode);

        if ( empty($addresses) ){
            return array();
        }

        $addressArray = [];
        
        foreach ($addresses as $address) {
            
            $addressArray[] = [
                'Id' => $address->uprn,
                'Summary' => trim($this->getSummary($address)),
                'Detail' => $this->getAddressLines($address),
            ];

        }

        return $addressArray;

        /**
         * Example response format:

            [0]=> array(3) {
                ["Id"]=>
                string(11) "51749629.00"
                ["StreetAddress"]=>
                string(29) "Apartment 201 8 Walworth Road"
                ["Place"]=>
                string(10) "London SE1"
                ["Detail"]=> [
                    'line1' => string
                    'line2' => string
                    'line3' => string
                    'postcode' => string
                ]
            }
            [1]=> array(3) {
                ["Id"]=>
                string(11) "51749630.00"
                ["StreetAddress"]=>
                string(29) "Apartment 202 8 Walworth Road"
                ["Place"]=>
                string(10) "London SE1"
                ["Detail"]=> [
                    'line1' => string
                    'line2' => string
                    'line3' => string
                    'postcode' => string
                ]
            }
         */

    } // function

    public function getAddressLines( Address $address ) {

        $components = $this->getAddressComponents($address);
        
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

        $result = [];

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
        
        $result['postcode'] = $address->postcode;
        
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
    
    private function getSummary(Address $address)
    {
        $addressComponents = $this->getAddressComponents($address);
        
        return implode(', ', $addressComponents);
    }
    
    /**
     * Return an array of address elements from a string that is
     * delimited by the \n character
     * 
     * We remove the postcode from the end of the array
     * 
     * @param Address $address
     * @return array
     */
    private function getAddressComponents(Address $address)
    {
        $components = explode("\n", $address->formatted_address);
        
        return $this->removePostcodeFromArray($components, $address->postcode);
    }
    
    /**
     * Remove the postcode from the array, if it is the last element
     * 
     * @param array $array
     * @param string $postcode
     * 
     * @return array
     */
    private function removePostcodeFromArray($array, $postcode) {
        
        // We expect the last element to be the postcode which we don't want
        // We'll confirm that it is the postcode and then remove it from the array
        $postcodeFromComponents = strtolower(str_replace(' ', '', $array[count($array)-1]));
        $postcodeFromAddress = strtolower(str_replace(' ', '', $postcode));
        
        if ($postcodeFromAddress == $postcodeFromComponents) {
            array_pop($array);
        }
        
        return $array;
         
    }
} // class
