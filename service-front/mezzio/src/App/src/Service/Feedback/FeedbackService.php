<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\Feedback\FeedbackValidationException;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;

class FeedbackService
{
    public const EMAIL_FEEDBACK = 'email-feedback';

    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly LoggerInterface $logger,
        private readonly ?MailTransportInterface $mailTransport = null,
        private readonly string $sendFeedbackEmailTo = '',
    ) {
    }

    /**
     * @throws FeedbackValidationException
     */
    public function add(array $data): void
    {
        try {
            $this->apiClient->httpPost('/user-feedback', $data);
        } catch (ApiException $ex) {
            $this->logger->warning('Failed to send feedback data to the API', [
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            if ($ex->getStatusCode() === 400) {
                throw new FeedbackValidationException($ex->getMessage());
            }

            throw $ex;
        }

        if ($this->mailTransport !== null && $this->sendFeedbackEmailTo !== '') {
            $email = $data['email'] ?? 'No email given';
            $phone = $data['phone'] ?? 'No phone number given';

            $now = new DateTime('now');
            $now->setTimezone(new DateTimeZone('Europe/London'));

            $templateData = [
                'currentDateTime' => $now->format('Y/m/d H:i:s'),
                'rating' => $data['rating'] ?? '',
                'details' => $data['details'] ?? '',
                'email' => $email,
                'phone' => $phone,
                'fromPage' => $data['fromPage'] ?? '',
                'agent' => $data['agent'] ?? '',
            ];

            $mailParameters = new MailParameters(
                $this->sendFeedbackEmailTo,
                self::EMAIL_FEEDBACK,
                $templateData
            );

            $this->mailTransport->send($mailParameters);
        }
    }
}
