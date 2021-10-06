<?php

namespace ApplicationTest\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Message;
use Application\Model\Service\Mail\MessageFactory;
use Application\Model\Service\Mail\Transport\SendGridMailTransport;
use DateTime;
use Exception;
use Hamcrest\Matchers;
use Hamcrest\MatcherAssert;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Laminas\Mail\Transport\Exception\RuntimeException;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use SendGrid\Client as SendGridClient;
use SendGrid\Exception\InvalidRequest;
use SendGrid\Mail\HtmlContent as SendGridHtmlContent;
use SendGrid\Mail\From as SendGridFromEmailAddress;
use SendGrid\Mail\Mail as SendGridMail;
use SendGrid\Mail\PlainTextContent as SendGridPlainTextContent;
use SendGrid\Mail\To as SendGridToEmailAddress;

class SendGridMailTransportTest extends MockeryTestCase
{
    /**
     * @var $sendgridClient Client|MockInterface
     */
    private $sendgridClient;

    /**
     * @var $service TransportInterface
     */
    private $service;

    public function setUp(): void
    {
        $this->sendgridClient = Mockery::mock(SendGridClient::class);
        $this->messageFactory = Mockery::mock(MessageFactory::class);

        $this->transport = new SendGridMailTransport($this->sendgridClient, $this->messageFactory);
    }

    private function createSendGridEmail($html = null, $text = 'Text content', $categories = []): SendGridMail
    {
        $from = new SendGridFromEmailAddress('from@test.com');
        $to = new SendGridToEmailAddress('to@test.com');

        $email = new SendGridMail($from, $to);

        if (!is_null($text)) {
            $email->addContent(new SendGridPlainTextContent($text));
        }

        if (!is_null($html)) {
            $email->addContent(new SendGridHtmlContent($html));
        }

        foreach ($categories as $category) {
            $email->addCategory($category);
        }

        return $email;
    }

    public function testSendPlainText(): void
    {
        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(200);

        $send = Mockery::mock();
        $send->shouldReceive('post')
            ->withArgs([Matchers::equalTo($this->createSendGridEmail())])
            ->once()
            ->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')
            ->once()
            ->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody('Text content');

        $this->messageFactory->shouldReceive('createMessage')
            ->andReturn($message);

        $this->transport->send(new MailParameters('to@test.com'));
    }

    public function testSendMultipleToAddresses(): void
    {
        $expectedAddresses = ['to1@test.com', 'to2@test.com'];

        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(200);

        $send = Mockery::mock();
        $send->shouldReceive('post')
            ->with(Mockery::on(function ($email) use ($expectedAddresses) {
                $actualAddresses = array_map(function ($toAddress) {
                    return $toAddress->getEmailAddress();
                }, $email->getPersonalizations()[0]->getTos());

                MatcherAssert::assertThat(
                    $expectedAddresses,
                    Matchers::arrayContainingInAnyOrder($actualAddresses)
                );

                return true;
            }))
            ->once()
            ->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')->once()->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo($expectedAddresses);
        $message->setBody('Text content');

        $mailParameters = new MailParameters($expectedAddresses);

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->transport->send($mailParameters);
    }

    public function testSendWithCategories(): void
    {
        $expectedCategories = ['foo', 'bar'];

        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(200);

        $send = Mockery::mock();
        $send->shouldReceive('post')
            ->with(Mockery::on(function ($email) use ($expectedCategories) {
                $actualCategories = array_map(function ($category) {
                    return $category->getCategory();
                }, $email->getCategories());

                MatcherAssert::assertThat($expectedCategories, Matchers::equalTo($actualCategories));

                return true;
            }))
            ->once()
            ->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')->once()->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody('Text content');

        foreach ($expectedCategories as $expectedCategory) {
            $message->addCategory($expectedCategory);
        }

        $mailParameters = new MailParameters('to@test.com');

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->transport->send($mailParameters);
    }

    public function testSendHtmlOnlySetsPlainText(): void
    {
        $expectedContents = [
            ['type' => 'text/html', 'value' => '<HTML><body>Test html</body></HTML>'],
            ['type' => 'text/plain', 'value' => 'Test html']
        ];

        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(200);

        $send = Mockery::mock();
        $send->shouldReceive('post')
            ->with(Mockery::on(function ($email) use ($expectedContents) {
                // Check the SendMail\Mail created by the transport has
                // the correct html content and plaintext derived from it
                $actualContents = array_map(function ($content) {
                    return ['type' => $content->getType(), 'value' => $content->getValue()];
                }, $email->getContents());

                MatcherAssert::assertThat(
                    $expectedContents,
                    Matchers::containsInAnyOrder($actualContents)
                );

                return true;
            }))
            ->once()
            ->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')->once()->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $textHtml = new Part('<HTML><body>Test html</body></HTML>');
        $textHtml->setType(Mime::TYPE_HTML);

        $content = new \Laminas\Mime\Message();
        $content->setParts([$textHtml]);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody($content);

        $mailParameters = new MailParameters('to@test.com');

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->transport->send($mailParameters);
    }

    public function testSendPlainTextAndHtml(): void
    {
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

        $textContent = new Part('Text content');
        $textContent->setType(Mime::TYPE_TEXT);

        $textHtml = new Part('<HTML><body>Test html</body></HTML>');
        $textHtml->setType(Mime::TYPE_HTML);

        $content = new \Laminas\Mime\Message();
        $content->setParts([$textContent, $textHtml]);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody($content);

        $mailParameters = new MailParameters('to@test.com');

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->transport->send($mailParameters);
    }

    public function testSendPostReturns500(): void
    {
        $postResult = Mockery::mock();
        $postResult->shouldReceive('statusCode')->once()->andReturn(500);
        $postResult->shouldReceive('body')->once()->andReturn('Test error');

        $send = Mockery::mock();
        $send->shouldReceive('post')->once()->andReturn($postResult);

        $mail = Mockery::mock();
        $mail->shouldReceive('send')->once()->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')->once()->andReturn($mail);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody('Text content');

        $mailParameters = new MailParameters('to@test.com');

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email sending failed: Test error');

        $this->transport->send($mailParameters);
    }

    public function testSendNoFrom(): void
    {
        $message = new Message();

        $mailParameters = new MailParameters('to@test.com');

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message returns as invalid');

        $this->transport->send($mailParameters);
    }

    public function testSendNoTo(): void
    {
        $message = new Message();
        $message->setFrom('from@test.com');

        $mailParameters = new MailParameters('to@test.com');

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SendGrid requires at least one TO address');

        $this->transport->send($mailParameters);
    }

    public function testSendNoMessage(): void
    {
        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');

        $mailParameters = new MailParameters('to@test.com');

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No message content has been set');

        $this->transport->send($mailParameters);
    }

    public function testSendInvalidRequestException(): void
    {
        $send = Mockery::mock();
        $send->shouldReceive('post')
            ->withArgs([Matchers::equalTo($this->createSendGridEmail())])
            ->once()
            ->andThrow(new InvalidRequest());

        $mail = Mockery::mock();
        $mail->shouldReceive('send')
            ->once()
            ->andReturn($send);

        $this->sendgridClient->shouldReceive('mail')
            ->once()
            ->andReturn($mail);

        $message = new Message();
        $message->setFrom('from@test.com');
        $message->setTo('to@test.com');
        $message->setBody('Text content');

        $mailParameters = new MailParameters('to@test.com');

        $this->messageFactory->shouldReceive('createMessage')
            ->with($mailParameters)
            ->andReturn($message);

        $this->expectException(RuntimeException::class);

        $this->transport->send($mailParameters);
    }
}
