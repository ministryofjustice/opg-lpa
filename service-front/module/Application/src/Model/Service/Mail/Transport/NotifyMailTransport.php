<?php

namespace Application\Model\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use Application\Logging\LoggerTrait;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Transport\MailTransportInterface;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Transport\Exception\InvalidArgumentException as TransportInvalidArgumentException;

/**
 * Sends an email via the Notify API.
 * See https://docs.notifications.service.gov.uk/php.html
 */
class NotifyMailTransport implements MailTransportInterface
{
    use LoggerTrait;

    /**
     * Map from internal template IDs to Notify template IDs
     *
     * @var array Keys are local template IDs, as passed to send() via a MailParameters object;
     * values are Notify template IDs
     */
    private $templateMap;

    // Default values for $templateMap; values are Notify template IDs;
    // NB templates have to be maintained manually on the Notify site
    private $defaultTemplateMap = [
       AbstractEmailService::EMAIL_FEEDBACK => '3fb12879-7665-4ffe-a76f-ed90cde7a35d',
       AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE => '32aea199-3b82-4e2d-8228-f2cd8b58c40a',
       AbstractEmailService::EMAIL_LPA_REGISTRATION => '10cde4ec-ca11-4e92-8396-782e3e8dc9b1',
       AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT => '1657a34a-b61b-4dfa-a530-153462d45dc5',
       AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY => '85a14f80-813e-4e72-8dc5-5549d958a592',
       AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_VERIFY => '1dd980a2-deab-4a5b-802b-61566188496d',
       AbstractEmailService::EMAIL_PASSWORD_CHANGED => '856f6b93-a248-42ae-9580-5d0ff24b595e',
       AbstractEmailService::EMAIL_PASSWORD_RESET => 'a4f2c358-0484-431f-8148-6d1280d79f44',
       AbstractEmailService::EMAIL_PASSWORD_RESET_NO_ACCOUNT => '4f57dea9-5433-4c49-9a69-365ab60a3b95',
       AbstractEmailService::EMAIL_ACCOUNT_DUPLICATION_WARNING => '4c99eeff-6af9-4753-aae1-a5d46ea06815',
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
     * Send a mail message.
     *
     * If $mailParams contains multiple email addresses and sending
     * to one throws an exception, subsequent emails will not be sent.
     *
     * @param  MailParameters $mailParams
     * @throws Laminas\Mail\Exception\ExceptionInterface
     */
    public function send(MailParameters $mailParams): void
    {
        $templateRef = $mailParams->getTemplateRef();
        if (!array_key_exists($templateRef, $this->templateMap)) {
            throw new InvalidArgumentException(
                'Could not find Notify template for template reference ' . $templateRef
            );
        }

        $notifyTemplateId = $this->templateMap[$templateRef];
        $data = $mailParams->getData();

        // We could get clever and send these in parallel, but as we're only
        // likely to have a maximum of 2 email addresses to send to,
        // we just fire them off in serial
        foreach ($mailParams->getToAddresses() as $toAddress) {
            // sendEmail() may throw one of the following:
            // - Alphagov\Notifications\Exception\NotifyException
            // - Alphagov\Notifications\Exception\ApiException
            // ApiException extends NotifyException, so we can just catch that
            // and turn it into an instance of a Laminas\Mail\Exception\ExceptionInterface
            try {
                $this->client->sendEmail($toAddress, $notifyTemplateId, $data);
            } catch (NotifyException $ex) {
                $this->getLogger()->err(
                    'Failed sending email via Notify: ' . $ex->getMessage() . '\n' . $ex->getTraceAsString()
                );

                throw new TransportInvalidArgumentException($ex);
            }
        }
    }
}
