<?php

namespace Application\Model\Service\Feedback;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\Mail\Transport\MailTransport;
use Laminas\Mail\Exception\ExceptionInterface;

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
            $to = $this->getConfig()['sendFeedbackEmailTo'];

            $message = $this->createMessage($to, AbstractEmailService::EMAIL_FEEDBACK, $data);
            $this->getMailTransport()->send($message);

            return true;
        } catch (ExceptionInterface $ex) {
            $this->getLogger()->err("Exception while adding feedback from Feedback service\n" .
                $ex->getMessage() . "\n" . $ex->getTraceAsString());
        }

        return false;
    }
}
