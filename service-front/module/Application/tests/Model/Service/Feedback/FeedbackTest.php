<?php

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Mail\MailParameters;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Message;
use Exception;
use Hamcrest\Matchers;
use Mockery;
use Mockery\MockInterface;

class FeedbackTest extends AbstractEmailServiceTest
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
            $this->mailTransport
        );

        $this->apiClient = Mockery::mock(Client::class);
        $this->service->setApiClient($this->apiClient);
    }

    public function testAdd(): void
    {
        $this->apiClient->shouldReceive('httpPost')->andReturnTrue();

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once();

        $result = $this->service->add(['test' => 'data']);

        $this->assertTrue($result);
    }

    public function testAddException(): void
    {
        $this->apiClient->shouldReceive('httpPost')->andReturnTrue();

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once()
            ->andThrow(new InvalidArgumentException('Test exception'));

        $result = $this->service->add(['test' => 'data']);

        $this->assertFalse($result);
    }
}
