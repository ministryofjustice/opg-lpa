<?php

namespace Application\Model\Service\Feedback;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Exception;

class Feedback extends AbstractEmailService
{
    /**
     * Send feedback data to the feedback inbox using a template
     *
     * @param array $data
     * @return bool|string
     */
    public function sendMail(array $data)
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
