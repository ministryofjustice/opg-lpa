<?php

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Feedback\Feedback;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use Exception;
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

    public function setUp() : void
    {
        parent::setUp();

        $this->apiClient = Mockery::mock(Client::class);

        $this->service = new Feedback(
            $this->authenticationService,
            ['sendFeedbackEmailTo' => 'test@email.com'],
            $this->twigEmailRenderer,
            $this->mailTransport
        );

        $this->service->setApiClient($this->apiClient);
    }

    public function testAdd() : void
    {
        $this->apiClient->shouldReceive('httpPost')->andReturnTrue();

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-feedback', ['test' => 'data']])
            ->once();

        $result = $this->service->add(['test' => 'data']);

        $this->assertTrue($result);
    }

    public function testAddException() : void
    {
        $this->apiClient->shouldReceive('httpPost')->andReturnTrue();

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-feedback', ['test' => 'data']])
            ->once()
            ->andThrow(new Exception('Test exception'));

        $result = $this->service->add(['test' => 'data']);

        $this->assertFalse($result);
    }
}
