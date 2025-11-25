<?php

namespace Application\Model\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use MakeShared\Constants;
use MakeShared\Logging\LoggerTrait;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Exception\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;

/**
 * Sends an email via the Notify API.
 * See https://docs.notifications.service.gov.uk/php.html
 */
class NotifyMailTransport implements MailTransportInterface, LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * Map from internal template IDs to Notify template IDs
     *
     * Keys are local template IDs, as passed to send() via a MailParameters object;
     * values are Notify template IDs
     *
    /** @var array */
    private $templateMap;

    // Default values for $templateMap; values are Notify template IDs;
    // NB templates have to be maintained manually on the Notify site
    private $defaultTemplateMap = [
       AbstractEmailService::EMAIL_FEEDBACK => '3fb12879-7665-4ffe-a76f-ed90cde7a35d',
       AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE => '32aea199-3b82-4e2d-8228-f2cd8b58c40a',
       AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1 => 'e462a4f3-db4a-4748-aecb-7b1b5c653e58',
       AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2 => '6779a351-b53e-4267-8eb7-7f24193e3026',
       AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3 => 'b84aa41d-c94e-4bb7-8747-28b9d6ed0d6c',
       AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY => '85a14f80-813e-4e72-8dc5-5549d958a592',
       AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_VERIFY => '1dd980a2-deab-4a5b-802b-61566188496d',
       AbstractEmailService::EMAIL_PASSWORD_CHANGED => '856f6b93-a248-42ae-9580-5d0ff24b595e',
       AbstractEmailService::EMAIL_PASSWORD_RESET => 'a4f2c358-0484-431f-8148-6d1280d79f44',
       AbstractEmailService::EMAIL_PASSWORD_RESET_NO_ACCOUNT => '4f57dea9-5433-4c49-9a69-365ab60a3b95',
       AbstractEmailService::EMAIL_ACCOUNT_DUPLICATION_WARNING => '4c99eeff-6af9-4753-aae1-a5d46ea06815',
    ];

    /** @var NotifyClient */
    private $client;

    // Address used to test email functionality
    /** @var string */
    private $smokeTestEmailAddress;

    /**
     * MailTransport constructor
     *
     * @param NotifyClient $client
     * @param string $smokeTestEmailAddress Used for health check on Notify
     * @param array $templateMap Map from local template IDs to Notify template IDs;
     * set to $this->defaultTemplateMap if not specified
     */
    public function __construct(
        NotifyClient $client,
        ?string $smokeTestEmailAddress = null,
        ?array $templateMap = null
    ) {
        $this->client = $client;

        $this->smokeTestEmailAddress = $smokeTestEmailAddress;

        if (is_null($templateMap)) {
            $templateMap = $this->defaultTemplateMap;
        }
        $this->templateMap = $templateMap;
    }

    /**
     * Send a mail message.
     *
     * If $mailParameters contains multiple email addresses and sending
     * to one throws an exception, subsequent emails will not be sent.
     *
     * @param  MailParameters $mailParameters
     * @throws InvalidArgumentException
     */
    public function send(MailParameters $mailParameters): void
    {
        $templateRef = $mailParameters->getTemplateRef();
        if (!array_key_exists($templateRef, $this->templateMap)) {
            throw new InvalidArgumentException(
                'Could not find Notify template for template reference ' . $templateRef
            );
        }

        $notifyTemplateId = $this->templateMap[$templateRef];
        $data = $mailParameters->getData();

        // We could get clever and send these in parallel, but as we're only
        // likely to have a maximum of 2 email addresses to send to,
        // we just fire them off in serial
        foreach ($mailParameters->getToAddresses() as $toAddress) {
            // sendEmail() may throw one of the following:
            // - Alphagov\Notifications\Exception\NotifyException
            // - Alphagov\Notifications\Exception\ApiException
            // ApiException extends NotifyException, so we can just catch that
            // and turn it into an instance of a use Application\Model\Service\Mail\Exception\InvalidArgumentException
            try {
                $this->client->sendEmail($toAddress, $notifyTemplateId, $data);
            } catch (NotifyException $ex) {
                $this->getLogger()->error('Failed sending email via Notify', [
                    'error_code' => 'NOTIFY_SEND_FAILURE',
                    'status' => $ex->getStatusCode(),
                    'exception' => $ex,
                ]);

                throw new InvalidArgumentException($ex->getMessage());
            }
        }
    }

    /**
     * Check whether the mail transport is functioning correctly.
     * This "sends" a feedback email to one of the smoke testing
     * email addresses described here:
     * https://docs.notifications.service.gov.uk/php.html#smoke-testing
     *
     * @return array
     */
    public function healthcheck(): array
    {
        $result = [
            'ok' => true,
            'status' => Constants::STATUS_PASS,
            'details' => [
                'smokeTestEmailAddress' => $this->smokeTestEmailAddress,
            ],
        ];

        $data = [
            'rating' => '',
            'currentDateTime' => '',
            'details' => '',
            'email' => $this->smokeTestEmailAddress,
            'phone' => '',
            'fromPage' => '',
            'agent' => '',
        ];

        try {
            $this->client->sendEmail(
                $this->smokeTestEmailAddress,
                $this->templateMap[AbstractEmailService::EMAIL_FEEDBACK],
                $data,
            );
        } catch (NotifyException $ex) {
            $this->getLogger()->error('Healthcheck on Notify failed', [
                'error_code' => 'NOTIFY_HEALTHCHECK_FAILURE',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            $result['ok'] = false;
            $result['status'] = Constants::STATUS_FAIL;
            $result['details']['exception'] = 'Unable to send email to smoke test address';
        }

        return $result;
    }
}
