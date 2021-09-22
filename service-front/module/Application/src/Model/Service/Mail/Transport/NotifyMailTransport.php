<?php

namespace Application\Model\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Application\Logging\LoggerTrait;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Transport\MailTransportInterface;

/**
 * Sends an email via the Notify API.
 */
class NotifyMailTransport implements MailTransportInterface
{
    use LoggerTrait;

    /**
     * Notify client object
     *
     * @var NotifyClient
     */
    private $client;

    /**
     * MailTransport constructor
     *
     * @param NotifyClient $client
     */
    public function __construct(NotifyClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send a mail message
     *
     * @param  MailParameters $message
     * @throws Laminas\Mail\Exception\ExceptionInterface
     */
    public function send(MailParameters $mailParams): void
    {
        $this->getLogger()->debug('@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ EMAIL VIA NOTIFY: ' . print_r($mailParams, true));

        foreach ($mailParams->getToAddresses() as $toAddress) {
            // hard-coded to Notify template email-account-activate (for account activation)
            $response = $this->client->sendEmail($toAddress, '32aea199-3b82-4e2d-8228-f2cd8b58c40a');
            $this->getLogger()->debug(print_r($response, true));
        }
    }
}
