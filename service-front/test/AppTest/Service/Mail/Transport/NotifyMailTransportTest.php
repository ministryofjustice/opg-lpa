<?php

declare(strict_types=1);

namespace AppTest\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use App\Service\Mail\Exception\InvalidArgumentException;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\NotifyMailTransport;
use App\Service\UserDetails;
use Laminas\Http\Response;
use MakeShared\Constants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class NotifyMailTransportTest extends TestCase
{
    private const FEEDBACK_TEMPLATE_ID = '3fb12879-7665-4ffe-a76f-ed90cde7a35d';
    private const PASSWORD_RESET_TEMPLATE_ID = 'a4f2c358-0484-431f-8148-6d1280d79f44';

    private NotifyClient&MockObject $client;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->client = $this->createMock(NotifyClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSendSendsEachRecipientUsingMappedTemplate(): void
    {
        $transport = $this->createTransport();
        $mailParameters = new MailParameters(
            ['one@example.com', 'two@example.com'],
            UserDetails::EMAIL_PASSWORD_RESET,
            ['token' => 'abc123']
        );

        $calls = [];

        $this->client->expects($this->exactly(2))
            ->method('sendEmail')
            ->willReturnCallback(function (string $toAddress, string $templateId, array $data) use (&$calls): void {
                $calls[] = [$toAddress, $templateId, $data];
            });

        $transport->send($mailParameters);

        $this->assertSame(
            [
                ['one@example.com', self::PASSWORD_RESET_TEMPLATE_ID, ['token' => 'abc123']],
                ['two@example.com', self::PASSWORD_RESET_TEMPLATE_ID, ['token' => 'abc123']],
            ],
            $calls
        );
    }

    public function testSendThrowsWhenTemplateReferenceIsUnknown(): void
    {
        $transport = $this->createTransport();

        $this->client->expects($this->never())->method('sendEmail');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not find Notify template for template reference unknown-template');

        $transport->send(new MailParameters('person@example.com', 'unknown-template', []));
    }

    public function testSendLogsAndWrapsNotifyFailures(): void
    {
        $transport = $this->createTransport();
        $exception = new NotifyException('Notify is unavailable');

        $this->client->expects($this->once())
            ->method('sendEmail')
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed sending email via Notify',
                $this->callback(
                    static fn (array $context): bool =>
                        $context['status'] === Response::STATUS_CODE_500
                        && $context['exception'] === $exception
                )
            );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Notify is unavailable');

        $transport->send(new MailParameters('person@example.com', UserDetails::EMAIL_PASSWORD_RESET, ['token' => 'abc123']));
    }

    public function testHealthcheckReturnsPassingResultWhenSmokeTestSucceeds(): void
    {
        $transport = $this->createTransport('smoke@example.com');

        $this->client->expects($this->once())
            ->method('sendEmail')
            ->with(
                'smoke@example.com',
                self::FEEDBACK_TEMPLATE_ID,
                $this->callback(static function (array $data): bool {
                    return $data['email'] === 'smoke@example.com'
                        && $data['rating'] === ''
                        && $data['details'] === ''
                        && $data['currentDateTime'] === '';
                })
            );
        $this->logger->expects($this->never())->method('error');

        $this->assertSame(
            [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => ['smokeTestEmailAddress' => 'smoke@example.com'],
            ],
            $transport->healthcheck()
        );
    }

    public function testHealthcheckLogsFailureAndReturnsFailingResult(): void
    {
        $transport = $this->createTransport('smoke@example.com');
        $exception = new NotifyException('boom');

        $this->client->expects($this->once())
            ->method('sendEmail')
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Healthcheck on Notify failed',
                $this->callback(
                    static fn (array $context): bool =>
                        $context['status'] === Response::STATUS_CODE_500
                        && $context['exception'] === $exception
                )
            );

        $this->assertSame(
            [
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
                'details' => [
                    'smokeTestEmailAddress' => 'smoke@example.com',
                    'exception' => 'Unable to send email to smoke test address',
                ],
            ],
            $transport->healthcheck()
        );
    }

    private function createTransport(?string $smokeTestEmailAddress = null): NotifyMailTransport
    {
        $transport = new NotifyMailTransport($this->client, $smokeTestEmailAddress);
        $transport->setLogger($this->logger);

        return $transport;
    }
}
