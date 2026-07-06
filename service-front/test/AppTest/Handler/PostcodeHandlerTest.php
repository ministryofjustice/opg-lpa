<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\PostcodeHandler;
use App\Service\AddressLookup\OrdnanceSurvey;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PostcodeHandlerTest extends TestCase
{
    private OrdnanceSurvey&MockObject $addressLookup;
    private LoggerInterface&MockObject $logger;
    private PostcodeHandler $handler;

    protected function setUp(): void
    {
        $this->addressLookup = $this->createMock(OrdnanceSurvey::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new PostcodeHandler($this->addressLookup, $this->logger);
    }

    private function createRequest(?string $postcode = null): ServerRequest
    {
        $queryParams = [];

        if ($postcode !== null) {
            $queryParams['postcode'] = $postcode;
        }

        return (new ServerRequest())
            ->withMethod('GET')
            ->withQueryParams($queryParams);
    }

    public function testReturnsAddressesForValidPostcode(): void
    {
        $addresses = [
            ['line1' => '1 Test Street', 'postcode' => 'SW1A 1AA'],
        ];

        $this->addressLookup
            ->expects($this->once())
            ->method('lookupPostcode')
            ->with('SW1A 1AA')
            ->willReturn($addresses);

        $response = $this->handler->handle($this->createRequest('SW1A 1AA'));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'isPostcodeValid' => true,
            'success' => true,
            'addresses' => $addresses,
        ], json_decode((string) $response->getBody(), true));
    }

    public function testReturnsFailureForEmptyPostcode(): void
    {
        $this->addressLookup->expects($this->never())->method('lookupPostcode');

        $response = $this->handler->handle($this->createRequest(''));

        $this->assertEquals([
            'isPostcodeValid' => false,
            'success' => false,
            'addresses' => [],
        ], json_decode((string) $response->getBody(), true));
    }

    public function testReturnsFailureForMissingPostcode(): void
    {
        $this->addressLookup->expects($this->never())->method('lookupPostcode');

        $response = $this->handler->handle($this->createRequest());

        $this->assertEquals([
            'isPostcodeValid' => false,
            'success' => false,
            'addresses' => [],
        ], json_decode((string) $response->getBody(), true));
    }

    public function testReturnsLookupFailureWhenLookupThrowsRuntimeException(): void
    {
        $exception = new RuntimeException('lookup failed');

        $this->addressLookup
            ->expects($this->once())
            ->method('lookupPostcode')
            ->with('SW1A 1AA')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Exception from postcode lookup', ['exception' => $exception]);

        $response = $this->handler->handle($this->createRequest('SW1A 1AA'));

        $this->assertEquals([
            'isPostcodeValid' => true,
            'success' => false,
            'addresses' => [],
        ], json_decode((string) $response->getBody(), true));
    }
}
