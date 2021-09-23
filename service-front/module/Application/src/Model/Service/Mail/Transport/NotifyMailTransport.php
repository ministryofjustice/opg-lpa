<?php

namespace Application\Model\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Application\Logging\LoggerTrait;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Transport\MailTransportInterface;

/**
 * Sends an email via the Notify API.
 */
class NotifyMailTransport implements MailTransportInterface
{
    use LoggerTrait;

    /**
     * Map from internal template IDs to Notify template IDs
     *
     * @var array Keys are local template IDs, as passed in a MailParameters object;
     * values are Notify template IDs
     */
    private $templateMap;

    // Default values for $templateMap
    private $defaultTemplateMap = [
       AbstractEmailService::EMAIL_FEEDBACK => '3fb12879-7665-4ffe-a76f-ed90cde7a35d',
       AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE => '32aea199-3b82-4e2d-8228-f2cd8b58c40a',
       AbstractEmailService::EMAIL_LPA_REGISTRATION => '10cde4ec-ca11-4e92-8396-782e3e8dc9b1',
       AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT => '1657a34a-b61b-4dfa-a530-153462d45dc5',
    ];

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
     * @param array $templateMap Map from local template IDs to Notify template IDs;
     * set to $this->defaultTemplateMap if not specified
     */
    public function __construct(NotifyClient $client, array $templateMap = null)
    {
        $this->client = $client;

        if (is_null($templateMap)) {
            $templateMap = $this->defaultTemplateMap;
        }
        $this->templateMap = $templateMap;
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

        // TODO check for invalid template ref
        $notifyTemplateId = $this->templateMap[$mailParams->getTemplateRef()];

        foreach ($mailParams->getToAddresses() as $toAddress) {
            $response = $this->client->sendEmail(
                $toAddress,
                $notifyTemplateId,
                $mailParams->getData()
            );

            $this->getLogger()->debug(print_r($response, true));
        }
    }
}
