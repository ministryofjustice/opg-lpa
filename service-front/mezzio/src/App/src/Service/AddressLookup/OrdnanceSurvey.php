<?php

declare(strict_types=1);

namespace App\Service\AddressLookup;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient as HttpClientInterface;
use RuntimeException;

class OrdnanceSurvey
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $endpoint;

    public function __construct(HttpClientInterface $client, string $apiKey, string $endpoint)
    {
        $this->httpClient = $client;
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
    }

    public function lookupPostcode(string $postcode): array
    {
        $results = $this->getData($postcode);

        $addresses = [];

        foreach ($results as $addressData) {
            $address = $this->getAddressLines($addressData['DPA']);
            $address['description'] = $this->getDescription($address);

            $addresses[] = $address;
        }

        return $addresses;
    }

    public function verify(array $response): bool
    {
        $addr = $response[0];
        // Checks the fields that we display in UI are present in response
        return isset($addr['line1']) && isset($addr['line2']) && isset($addr['line3']) && isset($addr['postcode']);
    }

    private function getData(string $postcode): array
    {
        $url = new Uri($this->endpoint);
        $url = Uri::withQueryValue($url, 'key', $this->apiKey);
        $url = Uri::withQueryValue($url, 'postcode', $postcode);
        $url = Uri::withQueryValue($url, 'lr', 'EN');

        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
        ];

        $request = new Request('GET', $url, $headers);

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Error retrieving address details: bad status code');
        }

        $body = json_decode(strval($response->getBody()), true);

        if (isset($body['header']['totalresults']) && $body['header']['totalresults'] === 0) {
            return [];
        }

        if (!isset($body['results']) || !is_array($body['results'])) {
            throw new RuntimeException('Error retrieving address details: invalid JSON');
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
     */
    private function getAddressLines(array $address): array
    {
        // Remove unwanted commas from the address, before we split on the commas.
        $reformattedAddress = preg_replace('/^([0-9-]+\w?),/', '$1', $address['ADDRESS']);
        $reformattedAddress = preg_replace('/,\s([0-9-]+\w?),/', ', $1', $reformattedAddress);

        // Construct the address into a line
        // Get the address lines into an array but remove the postcode from the end of
        // it so we can parse the address into 3 lines below
        $components = explode(",", $reformattedAddress);

        // We expect the last element to be the postcode which we don't want
        $postcodeFromComponents = strtolower(str_replace(' ', '', $components[count($components) - 1]));
        $postcodeFromAddress = strtolower(str_replace(' ', '', $address['POSTCODE']));

        if ($postcodeFromAddress == $postcodeFromComponents) {
            array_pop($components);
        }

        // Convert address to 3 lines plus a postcode
        $count = count($components);

        // By default assume there is 1 field per line.
        $numOnLine = [1 => 1, 2 => 1, 3 => 1];

        // When there are > 3, the fields change...
        if ($count > 3) {
            $numOnLine[1] = intval(floor($count / 3));
            $numOnLine[2] = $numOnLine[1];
            if (($count % 3) == 2) {
                $numOnLine[2]++;
            }
            $numOnLine[3] = $numOnLine[2];
            if (($count % 3) == 1) {
                $numOnLine[3]++;
            }
        }

        $result = [];

        for ($i = 1; $i <= 3; $i++) {
            $result["line{$i}"] = '';

            for ($j = 0; $j < $numOnLine[$i]; $j++) {
                if (!current($components)) {
                    break;
                }
                $result["line{$i}"] .= ', ' . trim(array_shift($components));
            }

            $result["line{$i}"] = ltrim($result["line{$i}"], ', ');
        }

        $result['postcode'] = $address['POSTCODE'];

        return $result;
    }

    private function getDescription(array $address): string
    {
        unset($address['postcode']);
        $address = array_filter($address);

        return trim(implode(', ', $address));
    }
}
