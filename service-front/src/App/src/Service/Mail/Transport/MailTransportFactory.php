<?php

declare(strict_types=1);

namespace App\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class MailTransportFactory
{
    public function __invoke(ContainerInterface $container): MailTransportInterface
    {
        $config = $container->get('config');
        $emailConfig = $config['email'] ?? [];
        $notifyKey = $emailConfig['notify']['key'];

        if (!$notifyKey) {
            return new NullMailTransport();
        }

        $smokeTestEmail = $emailConfig['notify']['smokeTestEmailAddress'];

        return new NotifyMailTransport(
            new NotifyClient([
                'apiKey'     => $notifyKey,
                'httpClient' => new GuzzleClient(),
            ]),
            $smokeTestEmail,
            $container->get(LoggerInterface::class),
        );
    }
}
