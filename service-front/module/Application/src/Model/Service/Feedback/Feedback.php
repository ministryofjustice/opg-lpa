<?php

namespace Application\Model\Service\Feedback;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Mail\Transport\MailTransport;
use Exception;

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

            //  Send the feedback via email also
            //  TODO - Remove this when we want to move fully to the DB stored feedback
            $this->sendMail($data);

            return true;
        } catch (ApiException $ex) {}

        return false;
    }

    /**
     * Send feedback data to the feedback inbox using a template
     *
     * @param array $data
     * @return bool|string
     */
    private function sendMail(array $data)
    {
        $to = $this->getConfig()['sendFeedbackEmailTo'];

        try {
            $this->getMailTransport()->sendMessageFromTemplate($to, MailTransport::EMAIL_FEEDBACK, $data);
        } catch (Exception $e) {
            return "failed-sending-email";
        }

        return true;
    }
}
