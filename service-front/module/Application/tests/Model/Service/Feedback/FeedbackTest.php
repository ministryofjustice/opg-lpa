<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Feedback\FeedbackValidationException;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

final class FeedbackTest extends AbstractEmailServiceTest
{
    private Client|MockInterface $apiClient;
    private Feedback $service;

    public function setUp(): void
    {
        parent::setUp();
        $identity = Mockery::mock();
        $identity->shouldReceive('id')->andReturn('1234');

        $this->authenticationService
            ->shouldReceive('getIdentity')
            ->andReturn($identity);

        $this->service = new Feedback(
            $this->authenticationService,
            $this->config,
            $this->mailTransport,
            $this->helperPluginManager
        );
        $logger = Mockery::spy(LoggerInterface::class);

        $this->apiClient = Mockery::mock(Client::class);
        $this->service->setApiClient($this->apiClient);
        $this->service->setLogger($logger);
    }

    public function testAdd(): void
    {
        $this->apiClient->shouldReceive('httpPost')->andReturnTrue();

        $templateData = [
            'rating' => 'very-satisfied',
            'details' => 'details',
            'email' => 'foo@bar.com',
            'phone' => '0111456789',
            'fromPage' => '/home',
            'agent' => 'Mozilla',
        ];

        // Check the data we interpolate into the template looks right
        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($mailParams) use ($templateData): true {
                $actualData = $mailParams->getData();

                $this->assertEqualsCanonicalizing(
                    [...array_keys($templateData), 'currentDateTime'],
                    array_keys($actualData)
                );

                $this->assertMatchesRegularExpression('/\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}/', $actualData['currentDateTime']);
                $this->assertEquals($templateData['rating'], $actualData['rating']);
                $this->assertEquals($templateData['details'], $actualData['details']);
                $this->assertEquals($templateData['email'], $actualData['email']);
                $this->assertEquals($templateData['phone'], $actualData['phone']);
                $this->assertEquals($templateData['fromPage'], $actualData['fromPage']);
                $this->assertEquals($templateData['agent'], $actualData['agent']);

                return true;
            }))
            ->once();

        $this->service->add($templateData);
    }

    public function testAddReturns400ExceptionAsValidationException(): void
    {
        $apiException = new ApiException(new Response(400, [], '{"detail":"a validation error occurred"}'));

        $this->apiClient->shouldReceive('httpPost')->andThrow($apiException);

        $this->expectException(FeedbackValidationException::class);
        $this->expectExceptionMessage('a validation error occurred');

        $this->service->add([]);
    }
}
