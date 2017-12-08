<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\SendgridController;
use Application\Model\Service\Mail\Transport\SendGrid;
use ApplicationTest\Controller\AbstractControllerTest;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Twig_Environment;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class SendgridControllerTest extends AbstractControllerTest
{
    /**
     * @var SendgridController
     */
    private $controller;
    private $postData = [
        'from' => 'unit@test.com',
        'to' => 'test@unit.com',
        'subject' => 'Subject',
        'spam_score' => 1,
        'text' => 'Text'
    ];
    /**
     * @var MockInterface|Twig_Environment
     */
    private $twigEmailRenderer;
    /**
     * @var MockInterface|SendGrid
     */
    private $mailTransport;

    public function setUp()
    {
        $this->controller = new SendgridController();
        parent::controllerSetUp($this->controller);

        $this->twigEmailRenderer = Mockery::mock(Twig_Environment::class);
        $this->serviceLocator->shouldReceive('get')->with('TwigEmailRenderer')->andReturn($this->twigEmailRenderer);

        $this->mailTransport = Mockery::mock(SendGrid::class);
        $this->serviceLocator->shouldReceive('get')->with('MailTransport')->andReturn($this->mailTransport);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testBounceActionBlankFromAddress()
    {
        $this->request->shouldReceive('getPost')->with('from')->andReturn(null)->once();
        $this->request->shouldReceive('getPost')->with('to')->andReturn($this->postData['to'])->once();
        $this->request->shouldReceive('getPost')->with('subject')->andReturn($this->postData['subject'])->once();
        $this->request->shouldReceive('getPost')->with('spam_score')->andReturn($this->postData['spam_score'])->once();
        $this->request->shouldReceive('getPost')->with('text')->andReturn($this->postData['text'])->once();

        $loggingData = [
            'from-address'          => '',
            'to-address'            => $this->postData['to'],
            'subject'               => $this->postData['subject'],
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false,
        ];

        $this->logger->shouldReceive('err')->with('Sender or recipient missing, or email sent to blackhole@lastingpowerofattorney.service.gov.uk - the message message will not be sent to SendGrid', $loggingData)->once();

        /** @var Response $result */
        $result = $this->controller->bounceAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('', $result->getContent());
    }

    public function testBounceActionEmptyToken()
    {
        $this->request->shouldReceive('getPost')->with('from')->andReturn($this->postData['from'])->once();
        $this->request->shouldReceive('getPost')->with('to')->andReturn($this->postData['to'])->once();
        $this->request->shouldReceive('getPost')->with('subject')->andReturn($this->postData['subject'])->once();
        $this->request->shouldReceive('getPost')->with('spam_score')->andReturn($this->postData['spam_score'])->once();
        $this->request->shouldReceive('getPost')->with('text')->andReturn($this->postData['text'])->once();

        $this->params->shouldReceive('fromRoute')->with('token')->andReturn('')->once();

        $loggingData = [
            'from-address'          => $this->postData['from'],
            'to-address'            => $this->postData['to'],
            'subject'               => $this->postData['subject'],
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false,
            'token'                 => ''
        ];

        $this->logger->shouldReceive('err')->with('Missing or invalid bounce token used', $loggingData)->once();

        /** @var Response $result */
        $result = $this->controller->bounceAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(403, $result->getStatusCode());
        $this->assertEquals('Invalid Token', $result->getContent());
    }

    public function testBounceActionSendEmailLogOnly()
    {
        $this->request->shouldReceive('getPost')->with('from')->andReturn('<' . $this->postData['from'] . '>')->once();
        $this->request->shouldReceive('getPost')->with('to')->andReturn($this->postData['to'])->once();
        $this->request->shouldReceive('getPost')->with('subject')->andReturn($this->postData['subject'])->once();
        $this->request->shouldReceive('getPost')->with('spam_score')->andReturn($this->postData['spam_score'])->once();
        $this->request->shouldReceive('getPost')->with('text')->andReturn($this->postData['text'])->once();

        $this->params->shouldReceive('fromRoute')->with('token')->andReturn('ValidToken')->once();
        $twigTemplate = Mockery::mock(Twig_Template::class);
        $this->twigEmailRenderer->shouldReceive('loadTemplate')->with('bounce.twig')->andReturn($twigTemplate)->once();
        $twigTemplate->shouldReceive('render')->with([])->andReturn('')->once();
        $this->mailTransport->shouldReceive('send')->never();

        $loggingData = [
            'from-address'          => '<' . $this->postData['from'] . '>',
            'to-address'            => $this->postData['to'],
            'subject'               => $this->postData['subject'],
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false,
        ];

        $this->logger->shouldReceive('info')->with('Logging SendGrid inbound parse usage - this will not trigger an email', $loggingData)->once();

        /** @var Response $result */
        $result = $this->controller->bounceAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('', $result->getContent());
    }

    public function testBounceActionSendEmailException()
    {
        $this->request->shouldReceive('getPost')->with('from')->andReturn($this->postData['from'])->once();
        $this->request->shouldReceive('getPost')->with('to')->andReturn($this->postData['to'])->once();
        $this->request->shouldReceive('getPost')->with('subject')->andReturn($this->postData['subject'])->once();
        $this->request->shouldReceive('getPost')->with('spam_score')->andReturn($this->postData['spam_score'])->once();
        $this->request->shouldReceive('getPost')->with('text')->andReturn($this->postData['text'])->once();

        $this->params->shouldReceive('fromRoute')->with('token')->andReturn('ValidToken')->once();
        $twigTemplate = Mockery::mock(Twig_Template::class);
        $this->twigEmailRenderer->shouldReceive('loadTemplate')->with('bounce.twig')->andReturn($twigTemplate)->once();
        $twigTemplate->shouldReceive('render')->with([])->andReturn('<!-- SUBJECT: Subject from template -->')->once();
        $this->mailTransport->shouldReceive('send')->never();

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
            'subject'               => 'Subject from template',
            'spam-score'            => $this->postData['spam_score'],
            'sent-from-windows-10'  => false,
            'token'                 => 'ValidToken'
        ];

        $exception = new Exception('Unit Test Exception');
        $this->logger->shouldReceive('info')->with('Logging SendGrid inbound parse usage - this will not trigger an email', $loggingData)->andThrow($exception)->once();
        $this->logger->shouldReceive('alert')->with("Failed sending email due to:\n" . $exception->getMessage(), $alertLoggingData)->once();

        $result = $this->controller->bounceAction();

        $this->assertEquals('failed-sending-email', $result);
    }
}