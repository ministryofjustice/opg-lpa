<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\NotificationsController;
use Application\Model\Service\Mail\Transport\MailTransport;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Header\HeaderInterface;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class NotificationsControllerTest extends AbstractControllerTest
{
    /**
     * @var NotificationsController
     */
    private $controller;
    private $validToken;
    /**
     * @var array
     */
    private $validPost;

    /**
     * @var MockInterface|MailTransport
     */
    private $mailTransport;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(NotificationsController::class);

        $this->validToken = $token = Mockery::mock(HeaderInterface::class);
        $this->validToken->shouldReceive('getFieldValue')->andReturn('validAccountCleanupToken');

        $this->validPost = [
            'Username' => 'unit@test.com',
            'Type' => '1-week-notice',
            'Date' => (new DateTime('+49 hours'))->format(DateTime::ISO8601)
        ];

        $this->mailTransport = Mockery::mock(MailTransport::class);
        $this->controller->setMailTransport($this->mailTransport);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testExpiryNoticeActionNoToken()
    {
        $this->request->shouldReceive('getHeader')->withArgs(['Token'])->andReturn(null)->once();

        /** @var Response $result */
        $result = $this->controller->expiryNoticeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(403, $result->getStatusCode());
        $this->assertEquals('Invalid Token', $result->getContent());
    }

    public function testExpiryNoticeActionInvalidToken()
    {
        $token = Mockery::mock(HeaderInterface::class);
        $token->shouldReceive('getFieldValue')->andReturn('InvalidToken');
        $this->request->shouldReceive('getHeader')->withArgs(['Token'])->andReturn($token)->once();

        /** @var Response $result */
        $result = $this->controller->expiryNoticeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(403, $result->getStatusCode());
        $this->assertEquals('Invalid Token', $result->getContent());
    }

    public function testExpiryNoticeActionMissingParameters()
    {
        $this->request->shouldReceive('getHeader')->withArgs(['Token'])->andReturn($this->validToken)->once();
        $this->request->shouldReceive('getPost')->once();

        /** @var Response $result */
        $result = $this->controller->expiryNoticeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('Missing parameters', $result->getContent());
    }

    public function testExpiryNoticeActionDateTooSoon()
    {
        $invalidDate = $this->validPost;
        $invalidDate['Date'] = (new DateTime('+47 hours'))->format(DateTime::ISO8601);

        $this->request->shouldReceive('getHeader')->withArgs(['Token'])->andReturn($this->validToken)->once();
        $this->request->shouldReceive('getPost')->andReturn($invalidDate)->once();

        /** @var Response $result */
        $result = $this->controller->expiryNoticeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('Date must be at least 48 hours in the future.', $result->getContent());
    }

    public function testExpiryNoticeActionInvalidType()
    {
        $invalidType = $this->validPost;
        $invalidType['Type'] = 'Invalid';

        $this->request->shouldReceive('getHeader')->withArgs(['Token'])->andReturn($this->validToken)->once();
        $this->request->shouldReceive('getPost')->andReturn($invalidType)->once();

        /** @var Response $result */
        $result = $this->controller->expiryNoticeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('Unknown type', $result->getContent());
    }

    public function testExpiryNoticeActionOneWeekNoticeSendException()
    {
        $validPost = $this->validPost;
        $validPost['Type'] = '1-week-notice';

        $this->request->shouldReceive('getHeader')->withArgs(['Token'])->andReturn($this->validToken)->once();
        $this->request->shouldReceive('getPost')->andReturn($validPost)->once();
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')->andThrow(new Exception('Unit test exception'))->once();

        /** @var Response $result */
        $result = $this->controller->expiryNoticeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Error receiving notification', $result->getContent());
    }

    public function testExpiryNoticeActionOneWeekNoticeSuccess()
    {
        $validPost = $this->validPost;
        $validPost['Type'] = '1-week-notice';

        $this->request->shouldReceive('getHeader')->withArgs(['Token'])->andReturn($this->validToken)->once();
        $this->request->shouldReceive('getPost')->andReturn($validPost)->once();
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')->once();

        /** @var Response $result */
        $result = $this->controller->expiryNoticeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Notification received', $result->getContent());
    }

    public function testExpiryNoticeActionOneMonthNoticeSuccess()
    {
        $validPost = $this->validPost;
        $validPost['Type'] = '1-month-notice';
        $email = null;

        $this->request->shouldReceive('getHeader')->withArgs(['Token'])->andReturn($this->validToken)->once();
        $this->request->shouldReceive('getPost')->andReturn($validPost)->once();
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')->once();

        /** @var Response $result */
        $result = $this->controller->expiryNoticeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Notification received', $result->getContent());
    }
}
