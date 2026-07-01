<?php

declare(strict_types=1);

namespace App\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Psr\Container\ContainerInterface;

final class MailTransportFactory
{
    public function __invoke(ContainerInterface $container): MailTransportInterface
    {
        $config = $container->get('config');
        $emailConfig = $config['email'] ?? [];

        $notifyKey = $emailConfig['notify']['key']
            ?? (getenv('OPG_LPA_FRONT_EMAIL_NOTIFY_API_KEY') ?: '');

        if (!$notifyKey) {
            return new NullMailTransport();
        }

        $smokeTestEmail = $emailConfig['notify']['smokeTestEmailAddress'] ?? null;

        return new NotifyMailTransport(
            new NotifyClient([
                'apiKey'     => $notifyKey,
                'httpClient' => new GuzzleClient(),
            ]),
            $smokeTestEmail,
        );
    }
}
