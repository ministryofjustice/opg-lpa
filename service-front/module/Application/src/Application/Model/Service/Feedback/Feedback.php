<?php

namespace Application\Model\Service\Feedback;

use Application\Model\Service\AbstractEmailService;
use Opg\Lpa\Logger\LoggerTrait;
use Exception;

class Feedback extends AbstractEmailService
{
    use LoggerTrait;

    public function sendMail($data)
    {
        $this->getLogger()->info('Sending feedback email', $data);

        $to = $this->getConfig()['sendFeedbackEmailTo'];

        $categories = [
            'opg',
            'opg-lpa',
            'opg-lpa-feedback',
        ];

        $data['sentTime'] = date('Y/m/d H:i:s');

        $subject = 'LPA v2 User Feedback';

        try {
            $this->getMailTransport()->sendMessageFromTemplate($to, $categories, $subject, 'feedback.twig', $data);
        } catch (Exception $e) {
            $this->getLogger()->err('Failed to send feedback email', $data);

            return "failed-sending-email";
        }

        return true;
    }
}
