<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\SendgridController;
use Application\Model\Service\Mail\Transport\MailTransport;
use ApplicationTest\Controller\AbstractControllerTest;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

class SendgridControllerTest extends AbstractControllerTest
{
    private $postData = [
        'from' => 'unit@test.com',
        'to' => 'test@unit.com',
        'subject' => 'Subject',
        'spam_score' => 1,
        'text' => 'Text'
    ];

    /**
     * @var MockInterface|MailTransport
     */
    private $mailTransport;

    public function setUp() : void
    {
        parent::setUp();

        $this->mailTransport = Mockery::mock(MailTransport::class);
    }

    public function testIndexAction()
    {
        /** @var SendgridController $controller */
        $controller = $this->getController(SendgridController::class);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testBounceActionBlankFromAddress()
    {
        /** @var SendgridController $controller */
        $controller = $this->getController(SendgridController::class);

        $this->request->shouldReceive('getPost')->withArgs(['from'])->andReturn(null)->once();
        $this->request->shouldReceive('getPost')->withArgs(['to'])->andReturn($this->postData['to'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['subject'])->andReturn($this->postData['subject'])->once();
        $this->request->shouldReceive('getPost')
            ->withArgs(['spam_score'])->andReturn($this->postData['spam_score'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['text'])->andReturn($this->postData['text'])->once();

        $loggingData = [
            'from-address'          => '',
            'to-address'            => $this->postData['to'],
            'subject'               => $this->postData['subject'],
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false,
        ];

        $this->logger->shouldReceive('err')->withArgs(['Sender or recipient missing, or email sent to blackhole@lastingpowerofattorney.service.gov.uk - the message message will not be sent to SendGrid', $loggingData])->once();

        /** @var Response $result */
        $result = $controller->bounceAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('', $result->getContent());
    }

    public function testBounceActionEmptyToken()
    {
        /** @var SendgridController $controller */
        $controller = $this->getController(SendgridController::class);

        $this->request->shouldReceive('getPost')->withArgs(['from'])->andReturn($this->postData['from'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['to'])->andReturn($this->postData['to'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['subject'])->andReturn($this->postData['subject'])->once();
        $this->request->shouldReceive('getPost')
            ->withArgs(['spam_score'])->andReturn($this->postData['spam_score'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['text'])->andReturn($this->postData['text'])->once();

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('')->once();

        $loggingData = [
            'from-address'          => $this->postData['from'],
            'to-address'            => $this->postData['to'],
            'subject'               => $this->postData['subject'],
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false,
            'token'                 => ''
        ];

        $this->logger->shouldReceive('err')->withArgs(['Missing or invalid bounce token used', $loggingData])->once();

        /** @var Response $result */
        $result = $controller->bounceAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(403, $result->getStatusCode());
        $this->assertEquals('Invalid Token', $result->getContent());
    }

    public function testBounceActionSendEmailLogOnly()
    {
        /** @var SendgridController $controller */
        $controller = $this->getController(SendgridController::class);

        $this->request->shouldReceive('getPost')
            ->withArgs(['from'])->andReturn('<' . $this->postData['from'] . '>')->once();
        $this->request->shouldReceive('getPost')->withArgs(['to'])->andReturn($this->postData['to'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['subject'])->andReturn($this->postData['subject'])->once();
        $this->request->shouldReceive('getPost')
            ->withArgs(['spam_score'])->andReturn($this->postData['spam_score'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['text'])->andReturn($this->postData['text'])->once();

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('ValidToken')->once();
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')->never();

        $loggingData = [
            'from-address'          => '<' . $this->postData['from'] . '>',
            'to-address'            => $this->postData['to'],
            'subject'               => $this->postData['subject'],
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false,
        ];

        $this->logger->shouldReceive('info')
            ->withArgs(['Logging SendGrid inbound parse usage - this will not trigger an email', $loggingData])->once();

        /** @var Response $result */
        $result = $controller->bounceAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('', $result->getContent());
    }

    public function testBounceActionSendEmailException()
    {
        /** @var SendgridController $controller */
        $controller = $this->getController(SendgridController::class);

        $this->request->shouldReceive('getPost')->withArgs(['from'])->andReturn($this->postData['from'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['to'])->andReturn($this->postData['to'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['subject'])->andReturn($this->postData['subject'])->once();
        $this->request->shouldReceive('getPost')
            ->withArgs(['spam_score'])->andReturn($this->postData['spam_score'])->once();
        $this->request->shouldReceive('getPost')->withArgs(['text'])->andReturn($this->postData['text'])->once();

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('ValidToken')->once();
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')->never();

        $loggingData = [
            'from-address'          => $this->postData['from'],
            'to-address'            => $this->postData['to'],
            'subject'               => $this->postData['subject'],
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false
        ];

        $alertLoggingData = [
            'from-address'          => $this->postData['from'],
            'to-address'            => $this->postData['to'],
            'subject'               => $this->postData['subject'],
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false,
            'token'                 => 'ValidToken'
        ];

        $exception = new Exception('Unit Test Exception');
        $this->logger->shouldReceive('info')
            ->withArgs(['Logging SendGrid inbound parse usage - this will not trigger an email', $loggingData])
            ->andThrow($exception)->once();
        $this->logger->shouldReceive('alert')
            ->withArgs(["Failed to send Sendgrid bounce email due to:\n" . $exception->getMessage(), $alertLoggingData])->once();

        $result = $controller->bounceAction();

        $this->assertEquals('failed-sending-email', $result);
    }
}
