<?php

namespace ApplicationTest\Model\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use Application\Model\Service\Mail\Exception\InvalidArgumentException;
use MakeShared\Constants;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Transport\NotifyMailTransport;
use Psr\Log\LoggerInterface;

class NotifyMailTransportTest extends MockeryTestCase
{
    public function setUp(): void
    {
        $this->notifyClient = Mockery::mock(NotifyClient::class);
        $logger = Mockery::spy(LoggerInterface::class);

        $this->transport = new NotifyMailTransport(
            $this->notifyClient,
            'foo@madeupaddress.bar',
        );
        $this->transport->setLogger($logger);
    }

    public function testSend(): void
    {
        $data = ['agent' => 'Mozilla'];

        $mailParams = new MailParameters(
            ['feedback@uat.digital.justice.gov.uk', 'other@uat.digital.justice.gov.uk'],
            AbstractEmailService::EMAIL_FEEDBACK,
            $data
        );

        $this->notifyClient->shouldReceive('sendEmail')
            ->with('feedback@uat.digital.justice.gov.uk', '3fb12879-7665-4ffe-a76f-ed90cde7a35d', $data)
            ->once();

        $this->notifyClient->shouldReceive('sendEmail')
            ->with('other@uat.digital.justice.gov.uk', '3fb12879-7665-4ffe-a76f-ed90cde7a35d', $data)
            ->once();

        $this->transport->send($mailParams);
    }

    public function testSendInvalidTemplateRef(): void
    {
        $mailParams = new MailParameters(
            'foo@uat.digital.justice.gov.uk',
            'invalidTemplateRef',
            []
        );

        $this->expectException(InvalidArgumentException::class);
        $this->transport->send($mailParams);
    }

    public function testSendNotifyClientThrowsException(): void
    {
        $mailParams = new MailParameters(
            'another@uat.digital.justice.gov.uk',
            AbstractEmailService::EMAIL_FEEDBACK,
            []
        );

        $this->notifyClient->shouldReceive('sendEmail')
            ->with('another@uat.digital.justice.gov.uk', '3fb12879-7665-4ffe-a76f-ed90cde7a35d', [])
            ->andThrow(new NotifyException());

        $this->expectException(InvalidArgumentException::class);
        $this->transport->send($mailParams);
    }

    public function testHealthcheckPass(): void
    {
        // No exception means the email was sent OK
        $this->notifyClient->shouldReceive('sendEmail')
            ->withArgs(function ($email, $templateId, $data) {
                return $email === 'foo@madeupaddress.bar' &&
                    $templateId === '3fb12879-7665-4ffe-a76f-ed90cde7a35d' &&
                    $data == [
                        'rating' => '',
                        'currentDateTime' => '',
                        'details' => '',
                        'email' => 'foo@madeupaddress.bar',
                        'phone' => '',
                        'fromPage' => '',
                        'agent' => '',
                    ];
            });

        $this->assertEquals(
            [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'smokeTestEmailAddress' => 'foo@madeupaddress.bar',
                ],
            ],
            $this->transport->healthcheck()
        );
    }

    public function testHealthcheckFail(): void
    {
        $this->notifyClient->shouldReceive('sendEmail')
            ->andThrow(new NotifyException());

        $this->assertEquals(
            [
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
                'details' => [
                    'smokeTestEmailAddress' => 'foo@madeupaddress.bar',
                    'exception' => 'Unable to send email to smoke test address',
                ],
            ],
            $this->transport->healthcheck()
        );
    }
}
