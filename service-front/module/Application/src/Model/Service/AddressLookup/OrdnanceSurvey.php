<?php
namespace Application\Model\Service\AddressLookup;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient as HttpClientInterface;

/**
 * Postcode Lookup service using Ordnance Survey data.
 *
 * Class OrdnanceSurvey
 * @package Application\Model\Service\AddressLookup
 */
class OrdnanceSurvey {

    /**
     * PSR-7 Compatible HTTP Client
     *
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * OrdnanceSurvey constructor.
     * @param HttpClientInterface $client
     * @param string $apiKey
     */
    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->httpClient = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * Return a list of addresses for a given postcode.
     *
     * @param $postcode
     * @return array
     * @throws \Http\Client\Exception
     */
    public function lookupPostcode($postcode)
    {
        $results = $this->getData($postcode);

        $addresses = [];

        foreach($results as $addressData){
            $address = $this->getAddressLines($addressData['DPA']);
            $address['description'] = $this->getDescription($address);

            $addresses[] = $address;
        }

        return $addresses;
    }

    /**
     * @param $postcode
     * @return mixed
     * @throws \Http\Client\Exception
     */
    private function getData($postcode)
    {
        $url = new Uri("https://api.ordnancesurvey.co.uk/places/v1/addresses/postcode");
        $url = URI::withQueryValue($url, 'key', $this->apiKey );
        $url = URI::withQueryValue($url, 'postcode', $postcode );
        $url = URI::withQueryValue($url, 'lr', 'EN' );

        $request = new Request('GET', $url);

        $response = $this->httpClient->sendRequest( $request );

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Error retrieving address details');
        }

        $body = json_decode($response->getBody(), true);

        if (!isset($body['results']) || !is_array($body['results'])){
            throw new \RuntimeException('Error retrieving address details');
        }

        return $body['results'];
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
     * @param array $address
     * @return array
     */
    private function getAddressLines(array $address)
    {

        // Remove unwanted commas from the address, before we split on the commas. Alternative: '/(\d+),/'
        $reformattedAddress = preg_replace('/^([0-9-]+\w?),/', '$1', $address['ADDRESS']);
        $reformattedAddress = preg_replace('/,\s([0-9-]+\w?),/', ', $1', $reformattedAddress);


        // Construct the address into a line
        //-----------------------------------------------------------------------
        //  Get the address lines into an array but remove the postcode from the end of
        //  it so we can parse the address into 3 lines below
        //  We will re-add the postcode as a final step
        $components = explode(",", $reformattedAddress);

        // We expect the last element to be the postcode which we don't want
        // We'll confirm that it is the postcode and then remove it from the array
        $postcodeFromComponents = strtolower(str_replace(' ', '', $components[count($components)-1]));
        $postcodeFromAddress = strtolower(str_replace(' ', '', $address['POSTCODE']));

        if ($postcodeFromAddress == $postcodeFromComponents) {
            array_pop($components);
        }


        //-----------------------------------------------------------------------
        // Convert address to 3 lines plus a postcode
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

        $result['postcode'] = $address['POSTCODE'];

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

}
