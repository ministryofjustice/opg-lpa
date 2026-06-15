<?php

declare(strict_types=1);

namespace App\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use App\Service\Mail\Exception\InvalidArgumentException;
use App\Service\Mail\MailParameters;
use App\Service\UserDetails;
use Laminas\Http\Response;
use MakeShared\Constants;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

class NotifyMailTransport implements MailTransportInterface, LoggerAwareInterface
{
    use LoggerTrait;

    private array $defaultTemplateMap = [
        UserDetails::EMAIL_FEEDBACK                              => '3fb12879-7665-4ffe-a76f-ed90cde7a35d',
        UserDetails::EMAIL_ACCOUNT_ACTIVATE                      => '32aea199-3b82-4e2d-8228-f2cd8b58c40a',
        UserDetails::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1        => 'e462a4f3-db4a-4748-aecb-7b1b5c653e58',
        UserDetails::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2 => '6779a351-b53e-4267-8eb7-7f24193e3026',
        UserDetails::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3     => 'b84aa41d-c94e-4bb7-8747-28b9d6ed0d6c',
        UserDetails::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY              => '85a14f80-813e-4e72-8dc5-5549d958a592',
        UserDetails::EMAIL_NEW_EMAIL_ADDRESS_VERIFY              => '1dd980a2-deab-4a5b-802b-61566188496d',
        UserDetails::EMAIL_PASSWORD_CHANGED                      => '856f6b93-a248-42ae-9580-5d0ff24b595e',
        UserDetails::EMAIL_PASSWORD_RESET                        => 'a4f2c358-0484-431f-8148-6d1280d79f44',
        UserDetails::EMAIL_PASSWORD_RESET_NO_ACCOUNT             => '4f57dea9-5433-4c49-9a69-365ab60a3b95',
        UserDetails::EMAIL_ACCOUNT_DUPLICATION_WARNING           => '4c99eeff-6af9-4753-aae1-a5d46ea06815',
    ];

    private array $templateMap;

    public function __construct(
        private readonly NotifyClient $client,
        private readonly ?string $smokeTestEmailAddress = null,
        ?array $templateMap = null,
    ) {
        $this->templateMap = $templateMap ?? $this->defaultTemplateMap;
    }

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

        foreach ($mailParameters->getToAddresses() as $toAddress) {
            try {
                $this->client->sendEmail($toAddress, $notifyTemplateId, $data);
            } catch (NotifyException $ex) {
                $this->getLogger()->error('Failed sending email via Notify', [
                    'status'    => Response::STATUS_CODE_500,
                    'exception' => $ex,
                ]);

                throw new InvalidArgumentException($ex->getMessage());
            }
        }
    }

    public function healthcheck(): array
    {
        $result = [
            'ok'      => true,
            'status'  => Constants::STATUS_PASS,
            'details' => [
                'smokeTestEmailAddress' => $this->smokeTestEmailAddress,
            ],
        ];

        $data = [
            'rating'          => '',
            'currentDateTime' => '',
            'details'         => '',
            'email'           => $this->smokeTestEmailAddress,
            'phone'           => '',
            'fromPage'        => '',
            'agent'           => '',
        ];

        try {
            $this->client->sendEmail(
                $this->smokeTestEmailAddress,
                $this->templateMap[UserDetails::EMAIL_FEEDBACK],
                $data,
            );
        } catch (NotifyException $ex) {
            $this->getLogger()->error('Healthcheck on Notify failed', [
                'status'    => Response::STATUS_CODE_500,
                'exception' => $ex,
            ]);

            $result['ok']                        = false;
            $result['status']                    = Constants::STATUS_FAIL;
            $result['details']['exception']      = 'Unable to send email to smoke test address';
        }

        return $result;
    }
}
