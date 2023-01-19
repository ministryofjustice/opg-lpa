<?php

namespace ApplicationTest\Model\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use Laminas\Mail\Exception\ExceptionInterface;
use Laminas\Mail\Exception\InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Transport\NotifyMailTransport;

class NotifyMailTransportTest extends MockeryTestCase
{
    /** @var NotifyClient */
    private $notifyClient;

    /** @var NotifyMailTransport */
    private $transport;

    public function setUp(): void
    {
        $this->notifyClient = Mockery::mock(NotifyClient::class);
        $this->transport = new NotifyMailTransport($this->notifyClient);
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

        $this->expectException(ExceptionInterface::class);
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

        $this->expectException(ExceptionInterface::class);
        $this->transport->send($mailParams);
    }
}
