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

class Feedback extends AbstractEmailService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * Send feedback data to the feedback inbox using a template
     *
     * @param array $data
     * @return bool|string
     */
    public function add(array $data)
    {
        try {
            $this->apiClient->httpPost('/user-feedback', $data);

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

            return true;
        } catch (ApiException $ex) {
            $this->getLogger()->error("API exception while adding feedback from Feedback service\n" .
                $ex->getMessage() . "\n" . $ex->getTraceAsString());
        } catch (InvalidArgumentException $ex) {
            $this->getLogger()->error("Mail exception while adding feedback from Feedback service\n" .
                $ex->getMessage() . "\n" . $ex->getTraceAsString());
        }

        return false;
    }
}
