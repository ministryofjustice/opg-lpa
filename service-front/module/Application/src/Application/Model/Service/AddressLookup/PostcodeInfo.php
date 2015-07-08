<?php
namespace Application\Model\Service\AddressLookup;

use RuntimeException;

use GuzzleHttp\Client as GuzzleClient;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\I18n\Validator\PostCode;
use MinistryOfJustice;
use MinistryOfJustice\PostcodeInfo\Client\Address;

/**
 * Postcode and address lookup from Postcode Anywhere.
 *
 * Class PostcodeInfo
 * @package Application\Model\Service\AddressLookup
 */
class PostcodeInfo implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;
    
    /**
     * Return a list of addresses for a given postcode.
     *
     * @param $postcode string A UK postcode
     * @return array Address list
     */
    public function lookupPostcode( $postcode )
    {

        $postcodeInfoClient = $this->getServiceLocator()->get('PostcodeInfoClient');
        
        $postcodeObj = $postcodeInfoClient->lookupPostcode($postcode);

        if (!$postcodeObj->isValid()) {
            return [];
        }

        $addressArray = [];
        
        foreach ($postcodeObj->getAddresses() as $address) {
            
            $addressId = $address->getUprn();
            
            $addressArray[] = [
                'Id' => $addressId,
                'StreetAddress' => trim($this->getStreetAddress($address)),
                'Place' => trim($this->getPlace($address)),
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

        //----------------------------------------------
        // Convert address to 3 lines plus a postcode

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
        
        $result['postcode'] = $address->getPostcode();
        
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
    
    private function getStreetAddress(Address $address)
    {
        $streetAddress = '';
    
        $streetAddress = $this->addWithSpaceIfNotEmpty($streetAddress, $address->getOrganisationName());
        $streetAddress = $this->addWithSpaceIfNotEmpty($streetAddress, $address->getDepartmentName());
        $streetAddress = $this->addWithSpaceIfNotEmpty($streetAddress, $address->getBuildingName());
        $streetAddress = $this->addWithSpaceIfNotEmpty($streetAddress, $address->getSubBuildingName());
        $streetAddress = $this->addWithSpaceIfNotEmpty($streetAddress, $address->getBuildingNumber());
        $streetAddress = $this->addWithSpaceIfNotEmpty($streetAddress, $address->getThoroughfareName());
        
        return $streetAddress;
    }
    
    private function getPlace(Address $address)
    {
        $place = '';
        
        $place = $this->addWithSpaceIfNotEmpty($place, $address->getPostTown());
        
        $postcodeParts = explode(' ', $address->getPostcode());
        
        $place = $this->addWithSpaceIfNotEmpty($place, $postcodeParts[0]);
        
        return $place;
    }
    
    /**
     * A helper function to append an address element to
     * an existing address if the element is not empty
     *
     * @param string $masterStr
     * @param string $candidateStr
     * 
     * @return string
     */
    private function addWithSpaceIfNotEmpty($masterStr, $candidateStr)
    {
        if (trim($candidateStr) != '') {
            $masterStr .= ' ' . $candidateStr;
        }
    
        return $masterStr;
    }
    
    /**
     * A helper function to add an address element to
     * an array if the element is not empty
     *
     * @param array $array
     * @param string $candidateStr
     * 
     * @return array
     */
    private function addToArrayIfNotEmpty(array $array, $candidateStr)
    {
        if (trim($candidateStr) != '') {
            $array[] = $candidateStr;
        }
    
        return $array;
    }
    
    private function getAddressComponents($address)
    {
        $components = [];
        
        $components = $this->addToArrayIfNotEmpty($components, $address->getOrganisationName());
        $components = $this->addToArrayIfNotEmpty($components, $address->getDepartmentName());
        
        if ($address->getPoBoxNumber() != '') {
            $components[] = 'PO BOX ' . $address->getPoBoxNumber();
        }
        
        $components = $this->addToArrayIfNotEmpty($components, $address->getBuildingName());
        $components = $this->addToArrayIfNotEmpty($components, $address->getSubBuildingName());
        $components = $this->addToArrayIfNotEmpty($components, $address->getBuildingNumber());
        $components = $this->addToArrayIfNotEmpty($components, $address->getThoroughfareName());
        $components = $this->addToArrayIfNotEmpty($components, $address->getDoubleDependentLocality());
        $components = $this->addToArrayIfNotEmpty($components, $address->getDependentLocality());
        $components = $this->addToArrayIfNotEmpty($components, $address->getPostTown());
        
        return $components;

    }
} // class
