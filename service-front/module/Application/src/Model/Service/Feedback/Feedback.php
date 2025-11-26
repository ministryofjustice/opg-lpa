<?php

namespace Application\Model\Service\Feedback;

use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Exception\InvalidArgumentException;
use DateTime;
use DateTimeZone;
use MakeShared\Logging\LoggerTrait;

class Feedback extends AbstractEmailService implements ApiClientAwareInterface
{
    use ApiClientTrait;
    use LoggerTrait;

    /**
     * Send feedback data to the feedback inbox using a template
     *
     * @throws FeedbackValidationException
     * @throws InvalidArgumentException
     * @param array $data
     */
    public function add(array $data): void
    {
        try {
            $this->apiClient->httpPost('/user-feedback', $data);
        } catch (ApiException $ex) {
            $this->getLogger()->warning('Failed send feedback data to the feedback inbox', [
                'error_code' => 'FAILED_SEND_FEEDBACK',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            if ($ex->getStatusCode() === 400) {
                throw new FeedbackValidationException($ex->getMessage());
            }

            throw $ex;
        }

        $email = 'No email given';
        if (isset($data['email'])) {
            $email = $data['email'];
        }

        $phone = 'No phone number given';
        if (isset($data['phone'])) {
            $phone = $data['phone'];
        }

        $now = new DateTime('now');
        $now->setTimezone(new DateTimeZone('Europe/London'));

        // Send the feedback via email also
        $templateData = [
            'currentDateTime' => $now->format('Y/m/d H:i:s'),
            'rating' => $data['rating'],
            'details' => $data['details'],
            'email' => $email,
            'phone' => $phone,
            'fromPage' => $data['fromPage'],
            'agent' => $data['agent'],
        ];

        $mailParameters = new MailParameters(
            $this->getConfig()['sendFeedbackEmailTo'],
            AbstractEmailService::EMAIL_FEEDBACK,
            $templateData
        );

        $this->getMailTransport()->send($mailParameters);
    }
}
