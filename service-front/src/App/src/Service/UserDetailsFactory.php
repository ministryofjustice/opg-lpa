<?php

declare(strict_types=1);

namespace App\Service;

use Alphagov\Notifications\Client as NotifyClient;
use App\Authentication\AuthenticationService;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use App\Service\Mail\Transport\NotifyMailTransport;
use App\Storage\MezzioSessionStorage;
use App\Service\ApiClient\Client as ApiClient;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserDetailsFactory
{
    public function __invoke(ContainerInterface $container): UserDetails
    {
        $authService = $container->get(AuthenticationService::class);
        $apiClient   = $container->get(ApiClient::class);
        $config                = $container->get('config');
        $emailConfig           = $config['email'] ?? [];
        $notifyKey             = $emailConfig['notify']['key'] ?? null;

        if ($notifyKey) {
            $mailTransport = new NotifyMailTransport(
                new NotifyClient([
                    'apiKey'     => $notifyKey,
                    'httpClient' => new GuzzleAdapter(),
                ]),
                $emailConfig['notify']['smokeTestEmailAddress'] ?? null,
            );
        } else {
            $mailTransport = new class implements MailTransportInterface {
                public function send(MailParameters $mailParameters): void
                {
                }
                public function healthcheck(): array
                {
                    return ['ok' => true, 'status' => 'ok'];
                }
            };
        }

        $userService = new UserDetails($authService, $config, $mailTransport);
        $userService->setUrlHelper($container->get(UrlHelper::class));
        $userService->setApiClient($apiClient);
        $userService->setLogger($container->get(LoggerInterface::class));
        $userService->setSessionStorage($container->get(MezzioSessionStorage::class));

        return $userService;
    }
}
