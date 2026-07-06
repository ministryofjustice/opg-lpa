<?php

declare(strict_types=1);

namespace AppTest\Service\AddressLookup;

use App\Service\AddressLookup\OrdnanceSurvey;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient as HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

final class OrdnanceSurveyTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    public function testConstructStoresDependencies(): void
    {
        $service = $this->createService();

        $this->assertSame($this->httpClient, (new \ReflectionProperty($service, 'httpClient'))->getValue($service));
        $this->assertSame('api-key', (new \ReflectionProperty($service, 'apiKey'))->getValue($service));
        $this->assertSame('https://example.com/addresses', (new \ReflectionProperty($service, 'endpoint'))->getValue($service));
    }

    public function testLookupPostcodeReturnsFormattedAddresses(): void
    {
        $service = $this->createService();

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(static function (RequestInterface $request): bool {
                parse_str($request->getUri()->getQuery(), $query);

                return $request->getMethod() === 'GET'
                    && (string) $request->getUri()->withQuery('') === 'https://example.com/addresses'
                    && $query === [
                        'key' => 'api-key',
                        'postcode' => 'SW1A 2AA',
                        'lr' => 'EN',
                    ]
                    && $request->getHeaderLine('Accept') === 'application/json'
                    && $request->getHeaderLine('Accept-Language') === 'en';
            }))
            ->willReturn(new Response(200, [], json_encode([
                'results' => [
                    [
                        'DPA' => [
                            'ADDRESS' => '10 Downing Street, Westminster, London, SW1A 2AA',
                            'POSTCODE' => 'SW1A 2AA',
                        ],
                    ],
                    [
                        'DPA' => [
                            'ADDRESS' => 'Flat 2, 10 Acacia Avenue, Springfield, London, Greater London, SW1A 2AA',
                            'POSTCODE' => 'SW1A 2AA',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR)));

        $this->assertSame([
            [
                'line1' => '10 Downing Street',
                'line2' => 'Westminster',
                'line3' => 'London',
                'postcode' => 'SW1A 2AA',
                'description' => '10 Downing Street, Westminster, London',
            ],
            [
                'line1' => 'Flat 2',
                'line2' => '10 Acacia Avenue, Springfield',
                'line3' => 'London, Greater London',
                'postcode' => 'SW1A 2AA',
                'description' => 'Flat 2, 10 Acacia Avenue, Springfield, London, Greater London',
            ],
        ], $service->lookupPostcode('SW1A 2AA'));
    }

    public function testLookupPostcodeReturnsEmptyArrayWhenNoResultsReturned(): void
    {
        $service = $this->createService();

        $this->httpClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode([
                'header' => ['totalresults' => 0],
            ], JSON_THROW_ON_ERROR)));

        $this->assertSame([], $service->lookupPostcode('SW1A 2AA'));
    }

    public function testLookupPostcodeThrowsForBadStatusCode(): void
    {
        $service = $this->createService();

        $this->httpClient->method('sendRequest')->willReturn(new Response(500));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error retrieving address details: bad status code');

        $service->lookupPostcode('SW1A 2AA');
    }

    public function testLookupPostcodeThrowsForInvalidJsonBody(): void
    {
        $service = $this->createService();

        $this->httpClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode(['unexpected' => true], JSON_THROW_ON_ERROR)));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error retrieving address details: invalid JSON');

        $service->lookupPostcode('SW1A 2AA');
    }

    public function testVerifyReturnsTrueWhenDisplayedAddressFieldsExist(): void
    {
        $service = $this->createService();

        $this->assertTrue($service->verify([[
            'line1' => '10 Downing Street',
            'line2' => 'Westminster',
            'line3' => 'London',
            'postcode' => 'SW1A 2AA',
        ]]));
    }

    public function testVerifyReturnsFalseWhenDisplayedAddressFieldMissing(): void
    {
        $service = $this->createService();

        $this->assertFalse($service->verify([[
            'line1' => '10 Downing Street',
            'line2' => 'Westminster',
            'postcode' => 'SW1A 2AA',
        ]]));
    }

    private function createService(): OrdnanceSurvey
    {
        return new OrdnanceSurvey($this->httpClient, 'api-key', 'https://example.com/addresses');
    }
}
