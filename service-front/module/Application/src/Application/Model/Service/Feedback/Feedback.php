<?php

namespace Application\Model\Service\Feedback;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Opg\Lpa\Logger\LoggerTrait;
use Exception;

class Feedback extends AbstractEmailService
{
    use LoggerTrait;

    public function sendMail($data)
    {
        $this->getLogger()->info('Sending feedback email', $data);

        $to = $this->getConfig()['sendFeedbackEmailTo'];

        $data['sentTime'] = date('Y/m/d H:i:s');

        try {
            $this->getMailTransport()->sendMessageFromTemplate($to, MailTransport::EMAIL_FEEDBACK, $data);
        } catch (Exception $e) {
            $this->getLogger()->err('Failed to send feedback email', $data);

            return "failed-sending-email";
        }

        return true;
    }
}
