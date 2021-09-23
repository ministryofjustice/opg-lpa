<?php

namespace Application\Model\Service\Feedback;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Transport\MailTransport;
use DateTime;
use Laminas\Mail\Exception\ExceptionInterface;

class Feedback extends AbstractEmailService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * Send feedback data to the feedback inbox using a template
     *
     * @param array $data
     * @return bool|string
     * @throws ExceptionInterface
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

            // Send the feedback via email also
            $templateData = [
                'currentDateTime' => (new DateTime('now', 'Europe/London'))->format('Y/m/d H:i:s'),
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
        } catch (ExceptionInterface $ex) {
            $this->getLogger()->err("Exception while adding feedback from Feedback service\n" .
                $ex->getMessage() . "\n" . $ex->getTraceAsString());
        }

        return false;
    }
}
