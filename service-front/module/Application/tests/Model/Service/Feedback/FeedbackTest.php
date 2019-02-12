<?php

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\Service\Feedback\Feedback;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use Exception;

class FeedbackTest extends AbstractEmailServiceTest
{
    public function setUp() : void
    {
        parent::setUp();
    }

    public function testSendMail() : void
    {
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-feedback', ['test' => 'data']])
            ->once();

        $service = new Feedback(
            $this->authenticationService,
            ['sendFeedbackEmailTo' => 'test@email.com'],
            $this->twigEmailRenderer,
            $this->mailTransport
        );

        $result = $service->sendMail(['test' => 'data']);

        $this->assertTrue($result);
    }

    public function testSendMailException() : void
    {
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-feedback', ['test' => 'data']])
            ->once()
            ->andThrow(new Exception('Test exception'));

        $service = new Feedback(
            $this->authenticationService,
            ['sendFeedbackEmailTo' => 'test@email.com'],
            $this->twigEmailRenderer,
            $this->mailTransport
        );

        $result = $service->sendMail(['test' => 'data']);

        $this->assertEquals('failed-sending-email', $result);
    }
}
