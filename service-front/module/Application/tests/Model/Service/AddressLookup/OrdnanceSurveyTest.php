<?php
namespace ApplicationTest\Model\Service\AddressLookup;

use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Http\Client\HttpClient as HttpClientInterface;
use Psr\Http\Message\ResponseInterface;

use GuzzleHttp\Psr7\Request;

use Application\Model\Service\AddressLookup\OrdnanceSurvey;

class OrdnanceSurveyTest extends MockeryTestCase
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var MockInterface|HttpClientInterface
     */
    private $httpClient;

    /**
     * @var MockInterface|ResponseInterface
     */
    private $response;

    protected function setUp()
    {
        $this->apiKey = 'test-key';
        $this->httpClient = Mockery::mock(HttpClientInterface::class);

        $this->response = Mockery::mock(ResponseInterface::class);
    }

    //------------------------------------------------------------------------------------
    // Lookup Tests

    public function testHttpLookupUrl()
    {
        $postcode = 'SW1A2AA';

        $this->response->shouldReceive('getStatusCode')->andReturn(200);
        $this->response->shouldReceive('getBody')->andReturn(json_encode([
            'results' => []
        ]));

        $this->httpClient->shouldReceive('sendRequest')
            ->withArgs(function ($arg) use ($postcode) {

                // It should be an instance of Request...
                if (!($arg instanceof Request)) {
                    return false;
                }

                // With the API key and postcode in the URL query.

                $query = $arg->getUri()->getQuery();
                if (strpos($query, "key={$this->apiKey}") === false) {
                    return false;
                }

                if (strpos($query, "postcode={$postcode}") === false) {
                    return false;
                }

                return true;
            })
            ->once()
            ->andReturn($this->response);

        $lookup = new OrdnanceSurvey($this->httpClient, $this->apiKey);

        $lookup->lookupPostcode($postcode);
    }


    public function testInvalidHttpLookupResponseCode()
    {
        $this->response->shouldReceive('getStatusCode')->andReturn(500);

        $this->httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($this->response);

        $lookup = new OrdnanceSurvey($this->httpClient, $this->apiKey);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp( '/bad status code/' );

        $lookup->lookupPostcode('SW1A 2AA');
    }

    public function testInvalidHttpLookupResponseBody()
    {
        $this->response->shouldReceive('getStatusCode')->andReturn(200);
        $this->response->shouldReceive('getBody')->andReturn('');   // <- Invalid JSON response

        $this->httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($this->response);

        $lookup = new OrdnanceSurvey($this->httpClient, $this->apiKey);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp( '/invalid JSON/' );

        $lookup->lookupPostcode('SW1A 2AA');
    }

    public function testValidHttpLookupResponse()
    {
        $this->response->shouldReceive('getStatusCode')->andReturn(200);
        $this->response->shouldReceive('getBody')->andReturn(json_encode([
            'results' => []
        ]));

        $this->httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($this->response);

        $lookup = new OrdnanceSurvey($this->httpClient, $this->apiKey);

        $result = $lookup->lookupPostcode('SW1A 2AA');

        // We expect an empty array.
        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    //------------------------------------------------------------------------------------
    // Formatting Tests

    private $testData = [
        [
            'address' => 'GOOD PUB, 10, DRINKING LANE, LONDON', 'postcode' => 'X1 3XX',
            'formatted' => ['GOOD PUB', '10 DRINKING LANE', 'LONDON', 'X1 3XX'],
        ],
        [
            'address' => 'FLAT 1, BOGGLE COURT, 5, TEE PARK, LONDON', 'postcode' => 'X1 3XX',
            'formatted' => ['FLAT 1', 'BOGGLE COURT', '5 TEE PARK, LONDON', 'X1 3XX'],
        ],
        [
            'address' => 'BIG BARN, LONG ROAD, FARMLAND', 'postcode' => 'X1 3XX',
            'formatted' => ['BIG BARN', 'LONG ROAD', 'FARMLAND', 'X1 3XX'],
        ],
        [
            'address' => '4, THE ROAD, LONDON', 'postcode' => 'X1 3XX',
            'formatted' => ['4 THE ROAD', 'LONDON', '', 'X1 3XX'],
        ],
    ];

    private function setupResponse()
    {
        $results = [];
        foreach ($this->testData as $address) {
            $results[] = [
                'DPA' => ['ADDRESS' => "{$address['address']}, {$address['postcode']}", 'POSTCODE'=>$address['postcode']]
            ];
        }

        $this->response->shouldReceive('getStatusCode')->andReturn(200);
        $this->response->shouldReceive('getBody')->andReturn(json_encode([
            'results' => $results
        ]));
    }

    public function testFormatting(){
        $this->setupResponse();
        $this->httpClient->shouldReceive('sendRequest')->once()->andReturn($this->response);

        $lookup = new OrdnanceSurvey($this->httpClient, $this->apiKey);
        $results = $lookup->lookupPostcode('X1 3XX');

        $this->assertInternalType('array', $results);
        $this->assertCount(count($this->testData), $results);

        // Loop over each entry in the test data
        foreach ($this->testData as $address) {

            // Get the relating entry from the result
            $result = array_shift($results);

            // For each expected line of formatted address
            foreach($address['formatted'] as $line) {
                // Check it matches the returned formatted line
                $this->assertEquals($line, array_shift($result));
            }
        }

    }

}
