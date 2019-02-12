<?php

namespace ApplicationTest\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\Transport\MailTransport;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use DateTime;
use Exception;
use Hamcrest\Matchers;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use SendGrid;
use SendGrid\Client;
use Zend\Mail\Message;
use Zend\Mime\Mime;
use Zend\Mime\Part;

class MailTransportTest extends AbstractEmailServiceTest
{
    /**
     * @var $sendgridClient Client|MockInterface
     */
    private $sendgridClient;

    /**
     * @var $service MailTransport
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->sendgridClient = Mockery::mock(Client::class);

        $this->service = new MailTransport(
            $this->sendgridClient,
            $this->twigEmailRenderer,
            [
                'sender' => [
                    'default' => [
                        'address' => 'sender@email.com',
                        'name' => 'Sender Name'
                    ]
                ]
            ]
        );
    }

    private function createSendGridEmail($html = null) : SendGrid\Mail
    {
        $textContent = $html ? new SendGrid\Content('text/plain', 'Text content') : 'Text content';

        $from = new SendGrid\Email(null, 'from@test.com');

        $email = new SendGrid\Mail($from, null, new SendGrid\Email(null, 'to@test.com'), $textContent);

        if ($html == null) {
            $email->addContent(null);
        } else {
            $email->addContent(new SendGrid\Content('text/html', $html));
        }

        return $email;
    }

    public function testSendPlainText() : void
    {
        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(200);

        $send = Mockery::mock();
        $send->shouldReceive('post')
            ->withArgs([Matchers::equalTo($this->createSendGridEmail())])
            ->once()
            ->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')->once()->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody('Text content');

        $this->service->send($message);
    }

    public function testSendPlainTextAndHtml() : void
    {
        $textContent = new Part('Text content');
        $textContent->setType(Mime::TYPE_TEXT);

        $textHtml = new Part('<HTML><body>Test html</body></HTML>');
        $textHtml->setType(Mime::TYPE_HTML);

        $content = new \Zend\Mime\Message();
        $content->setParts([$textContent, $textHtml]);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody($content);

        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(200);

        $send = Mockery::mock();
        $send->shouldReceive('post')
            ->withArgs([Matchers::equalTo($this->createSendGridEmail('<HTML><body>Test html</body></HTML>'))])
            ->once()
            ->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')->once()->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $this->service->send($message);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Email sending failed: Test error
     */
    public function testSendPostReturns500() : void
    {
        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody('Text content');

        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(500);
        $postResult->shouldReceive('body')->once()->andReturn('Test error');

        $send = Mockery::mock();
        $send->shouldReceive('post')->once()->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')->once()->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $this->service->send($message);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Mail\Message returns as invalid
     */
    public function testSendNoFrom() : void
    {
        $message = new Message();

        $this->service->send($message);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage SendGrid requires at least one TO address
     */
    public function testSendNoTo() : void
    {
        $message = new Message();
        $message->setFrom('from@test.com');

        $this->service->send($message);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No message content has been set
     */
    public function testSendNoMessage() : void
    {
        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');

        $this->service->send($message);
    }

    public function testSendMessageFromTemplate() : void
    {
        $template = Mockery::mock();
        $template->shouldReceive('render')->once()->andReturn('<html><!-- SUBJECT: TEST SUBJECT --><h1>An email</h1></html>');

        $this->twigEmailRenderer->shouldReceive('loadTemplate')
            ->withArgs(['registration.twig'])
            ->once()
            ->andReturn($template);

        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(200);

        // Omitting withArgs as the complexity of the objects prevents matching
        $send = Mockery::mock();
        $send->shouldReceive('post')
            ->once()
            ->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')->once()->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $this->service->sendMessageFromTemplate('to@email.com', 'email-account-activate', [], new DateTime('2019-01-01'));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Missing template config for MISSING-EMAIL-REF
     * @throws Exception
     */
    public function testSendMessageFromTemplateMissingTemplate() : void
    {
        $this->service->sendMessageFromTemplate('to@email.com', 'MISSING-EMAIL-REF', [], new DateTime('2019-01-01'));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Email subject can not be retrieved from the email template content
     * @throws Exception
     */
    public function testSendMessageFromTemplateMissingSubject() : void
    {
        $template = Mockery::mock();
        $template->shouldReceive('render')->once();

        $this->twigEmailRenderer->shouldReceive('loadTemplate')
            ->withArgs(['registration.twig'])
            ->once()
            ->andReturn($template);

        $this->service->sendMessageFromTemplate('to@email.com', 'email-account-activate', [], new DateTime('2019-01-01'));
    }
}
