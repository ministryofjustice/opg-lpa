<?php

declare(strict_types=1);

namespace AppTest\Service\Feedback;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\Feedback\FeedbackService;
use App\Service\Feedback\FeedbackValidationException;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

final class FeedbackServiceTest extends TestCase
{
    private ApiClient&MockObject $apiClient;
    private LoggerInterface&MockObject $logger;
    private MailTransportInterface&MockObject $mailTransport;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailTransport = $this->createMock(MailTransportInterface::class);
    }

    public function testAddCallsApiClientHttpPost(): void
    {
        $data = ['rating' => 5, 'details' => 'Very helpful'];

        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->with('/user-feedback', $data);

        $this->createService()->add($data);
    }

    public function testAddWithBadRequestThrowsFeedbackValidationException(): void
    {
        $exception = $this->makeApiException(400, 'invalid feedback');

        $this->apiClient->method('httpPost')->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to send feedback data to the API',
                $this->callback(static fn (array $context): bool => $context['status'] === 400 && $context['exception'] === $exception)
            );

        $this->expectException(FeedbackValidationException::class);
        $this->expectExceptionMessage('invalid feedback');

        $this->createService()->add(['details' => 'Invalid']);
    }

    public function testAddWithNonValidationApiExceptionRethrowsApiException(): void
    {
        $exception = $this->makeApiException(500, 'server error');

        $this->apiClient->method('httpPost')->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to send feedback data to the API',
                $this->callback(static fn (array $context): bool => $context['status'] === 500 && $context['exception'] === $exception)
            );

        try {
            $this->createService()->add(['details' => 'Invalid']);
            $this->fail('Expected ApiException to be thrown');
        } catch (ApiException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    public function testAddWithMailTransportSendsMailWhenConfigured(): void
    {
        $data = [
            'rating' => 5,
            'details' => 'Very helpful',
            'email' => 'person@example.com',
            'phone' => '01234 567890',
            'fromPage' => '/contact',
            'agent' => 'Firefox',
        ];

        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->with('/user-feedback', $data);
        $this->mailTransport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (MailParameters $mailParameters) use ($data): bool {
                $templateData = $mailParameters->getData();

                return $mailParameters->getToAddresses() === ['feedback@example.com']
                    && $mailParameters->getTemplateRef() === FeedbackService::EMAIL_FEEDBACK
                    && $templateData['rating'] === $data['rating']
                    && $templateData['details'] === $data['details']
                    && $templateData['email'] === $data['email']
                    && $templateData['phone'] === $data['phone']
                    && $templateData['fromPage'] === $data['fromPage']
                    && $templateData['agent'] === $data['agent']
                    && is_string($templateData['currentDateTime'])
                    && $templateData['currentDateTime'] !== '';
            }));

        $this->createService($this->mailTransport, 'feedback@example.com')->add($data);
    }

    public function testAddWithMailTransportDoesNotSendWhenRecipientIsEmpty(): void
    {
        $data = ['email' => 'person@example.com'];

        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->with('/user-feedback', $data);
        $this->mailTransport->expects($this->never())->method('send');

        $this->createService($this->mailTransport, '')->add($data);
    }

    private function createService(
        ?MailTransportInterface $mailTransport = null,
        string $sendFeedbackEmailTo = ''
    ): FeedbackService {
        return new FeedbackService(
            $this->apiClient,
            $this->logger,
            $mailTransport,
            $sendFeedbackEmailTo,
        );
    }

    private function makeApiException(int $statusCode, string $message): ApiException
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('{"detail":"' . $message . '"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);

        return new ApiException($response, $message);
    }
}
