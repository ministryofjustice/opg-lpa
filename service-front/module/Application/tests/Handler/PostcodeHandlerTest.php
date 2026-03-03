<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\PostcodeHandler;
use Application\Model\Service\AddressLookup\OrdnanceSurvey;
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

        $this->handler = new PostcodeHandler(
            $this->addressLookup,
            $this->logger,
        );
    }

    public function testReturns200WhenPostcodeNotProvided(): void
    {
        $request = new ServerRequest();

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals([
            'isPostcodeValid' => false,
            'success' => false,
            'addresses' => [],
        ], $body);
    }

    public function testReturns200WhenPostcodeIsEmpty(): void
    {
        $request = (new ServerRequest())->withQueryParams(['postcode' => '']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals([
            'isPostcodeValid' => false,
            'success' => false,
            'addresses' => [],
        ], $body);
    }

    public function testReturnsSuccessWithAddressesWhenPostcodeFound(): void
    {
        $postcode = 'SW1H 9AJ';
        $addresses = [
            [
                'line1' => '102 Petty France',
                'line2' => 'Westminster',
                'line3' => 'London',
                'postcode' => 'SW1H 9AJ',
                'description' => 'Ministry of Justice',
            ],
        ];

        $this->addressLookup
            ->expects($this->once())
            ->method('lookupPostcode')
            ->with($postcode)
            ->willReturn($addresses);

        $request = (new ServerRequest())->withQueryParams(['postcode' => $postcode]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals([
            'isPostcodeValid' => true,
            'success' => true,
            'addresses' => $addresses,
        ], $body);
    }

    public function testReturnsSuccessFalseWhenNoAddressesFound(): void
    {
        $postcode = 'XX1 1XX';

        $this->addressLookup
            ->expects($this->once())
            ->method('lookupPostcode')
            ->with($postcode)
            ->willReturn([]);

        $request = (new ServerRequest())->withQueryParams(['postcode' => $postcode]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals([
            'isPostcodeValid' => true,
            'success' => false,
            'addresses' => [],
        ], $body);
    }

    public function testLogsWarningAndReturnsSuccessFalseWhenRuntimeExceptionThrown(): void
    {
        $postcode = 'SW1H 9AJ';
        $exception = new RuntimeException('API error');

        $this->addressLookup
            ->expects($this->once())
            ->method('lookupPostcode')
            ->with($postcode)
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Exception from postcode lookup', $this->callback(function ($context) use ($exception) {
                return isset($context['exception']) && $context['exception'] === $exception;
            }));

        $request = (new ServerRequest())->withQueryParams(['postcode' => $postcode]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals([
            'isPostcodeValid' => true,
            'success' => false,
            'addresses' => [],
        ], $body);
    }
}
