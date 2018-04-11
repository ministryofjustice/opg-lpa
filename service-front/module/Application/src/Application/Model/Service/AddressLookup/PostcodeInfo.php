<?php

namespace Application\Model\Service\AddressLookup;

use Application\Model\Service\AbstractService;
use MinistryOfJustice\PostcodeInfo\Client as PostcodeInfoClient;
use MinistryOfJustice\PostcodeInfo\Response\Address;

/**
 * Postcode and address lookup from Postcode Anywhere.
 *
 * Class PostcodeInfo
 * @package Application\Model\Service\AddressLookup
 */
class PostcodeInfo extends AbstractService
{
    /**
     * @var PostcodeInfoClient
     */
    private $postCodeInfoClient;

    /**
     * Return a list of addresses for a given postcode.
     *
     * @param $postcode string A UK postcode
     * @return array Address list
     */
    public function lookupPostcode($postcode)
    {
        $addressObjs = $this->postCodeInfoClient->lookupPostcodeAddresses($postcode);

        if (empty($addressObjs)) {
            return [];
        }

        $addresses = [];

        foreach ($addressObjs as $addressObj) {
            //  Get the address lines and add a description
            $address = $this->getAddressLines($addressObj);
            $address['description'] = $this->getDescription($address);

            $addresses[] = $address;
        }

        return $addresses;
    }

    /**
     * Get the address in a standard format of...
     *  [
     *      'line1' => string
     *      'line2' => string
     *      'line3' => string
     *      'postcode' => string
     *  ]
     *
     * @param Address $address
     * @return array
     */
    private function getAddressLines(Address $address)
    {
        //-----------------------------------------------------------------------
        //  Get the address lines into an array but remove the postcode from the end of
        //  it so we can parse the address into 3 lines below
        //  We will re-add the postcode as a final step
        $components = explode("\n", $address->formatted_address);

        // We expect the last element to be the postcode which we don't want
        // We'll confirm that it is the postcode and then remove it from the array
        $postcodeFromComponents = strtolower(str_replace(' ', '', $components[count($components)-1]));
        $postcodeFromAddress = strtolower(str_replace(' ', '', $address->postcode));

        if ($postcodeFromAddress == $postcodeFromComponents) {
            array_pop($components);
        }


        //-----------------------------------------------------------------------
        // Convert address to 3 lines plus a postcode
        $count = count($components);

        # TODO - This could be moved out if we wanted to apply it in other classes.

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

        $result = [];

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

        //---

        $result['postcode'] = $address->postcode;

        //---

        return $result;
    }

    /**
     * Get a single line address description (without postcode)
     *
     * @param array $address
     * @return string
     */
    private function getDescription(array $address)
    {
        unset($address['postcode']);
        $address = array_filter($address);

        return trim(implode(', ', $address));
    }

    public function setPostcodeInfoClient(PostcodeInfoClient $postCodeInfoClient)
    {
        $this->postCodeInfoClient = $postCodeInfoClient;
    }
}
