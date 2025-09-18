<?php

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Mail\MailParameters;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use Application\Model\Service\Mail\Exception\InvalidArgumentException;
use Hamcrest\Matchers;
use Hamcrest\MatcherAssert;
use Mockery;
use Psr\Log\LoggerInterface;

final class FeedbackTest extends AbstractEmailServiceTest
{
    /**
     * @var $apiClient Client|MockInterface
     */
    private $apiClient;

    /**
     * @var $service Feedback
     */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

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

        $expectedData = [
            'currentDateTime' => Matchers::matchesPattern('/\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}/'),
            'rating' => Matchers::equalTo($templateData['rating']),
            'details' => Matchers::equalTo($templateData['details']),
            'email' => Matchers::equalTo($templateData['email']),
            'phone' => Matchers::equalTo($templateData['phone']),
            'fromPage' => Matchers::equalTo($templateData['fromPage']),
            'agent' => Matchers::equalTo($templateData['agent']),
        ];

        // Check the data we interpolate into the template looks right
        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($mailParams) use ($expectedData) {
                $actualData = $mailParams->getData();

                foreach ($expectedData as $key => $matcher) {
                    MatcherAssert::assertThat($actualData[$key], $matcher);
                }

                MatcherAssert::assertThat(
                    array_keys($actualData),
                    Matchers::equalTo(array_keys($expectedData))
                );

                return true;
            }))
            ->once();

        $result = $this->service->add($templateData);

        $this->assertTrue($result);
    }

    public function testAddException(): void
    {
        $this->apiClient->shouldReceive('httpPost')->andReturnTrue();

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once()
            ->andThrow(new InvalidArgumentException('Test exception'));

        $result = $this->service->add([
            'rating' => 'very-satisfied',
            'details' => 'details',
            'email' => '',
            'phone' => '',
            'fromPage' => '/home',
            'agent' => 'Mozilla',
        ]);

        $this->assertFalse($result);
    }
}
