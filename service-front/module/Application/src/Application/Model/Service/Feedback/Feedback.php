<?php

namespace Application\Model\Service\Feedback;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Opg\Lpa\Logger\LoggerTrait;
use Exception;

class Feedback extends AbstractEmailService
{
    use LoggerTrait;

    /**
     * Send feedback data to the feedback inbox using a template
     *
     * @param array $data
     * @return bool|string
     */
    public function sendMail(array $data)
    {
        $this->getLogger()->info('Sending feedback email', $data);

        $to = $this->getConfig()['sendFeedbackEmailTo'];

        try {
            $this->getMailTransport()->sendMessageFromTemplate($to, MailTransport::EMAIL_FEEDBACK, $data);
        } catch (Exception $e) {
            $this->getLogger()->err('Failed to send feedback email', $data);

            return "failed-sending-email";
        }

        return true;
    }
}
