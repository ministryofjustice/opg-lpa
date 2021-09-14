<?php

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Feedback\Feedback;
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
            $this->localViewRenderer,
            $this->mailTransport
        );

        $this->apiClient = Mockery::mock(Client::class);
        $this->service->setApiClient($this->apiClient);
    }

    public function testAdd(): void
    {
        $this->apiClient->shouldReceive('httpPost')->andReturnTrue();

        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->andReturn('<!-- SUBJECT: Delightful feedback time --><p>message content</p>');

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(Message::class))
            ->once();

        $result = $this->service->add(['test' => 'data']);

        $this->assertTrue($result);
    }

    public function testAddException(): void
    {
        $this->apiClient->shouldReceive('httpPost')->andReturnTrue();

        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->andReturn('<!-- SUBJECT: Broken feedback time --><p>message content</p>');

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(Message::class))
            ->once()
            ->andThrow(new InvalidArgumentException('Test exception'));

        $result = $this->service->add(['test' => 'data']);

        $this->assertFalse($result);
    }
}
