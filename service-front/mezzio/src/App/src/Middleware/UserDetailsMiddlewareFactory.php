<?php

declare(strict_types=1);

namespace App\Middleware;

use Alphagov\Notifications\Client as NotifyClient;
use App\Model\UserDetailsHolder;
use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Transport\MailTransportInterface;
use Application\Model\Service\Mail\Transport\NotifyMailTransport;
use Application\Model\Service\User\Details as UserService;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserDetailsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): UserDetailsMiddleware
    {
        $lpaApplicationService = $container->get(LpaApplicationService::class);
        $authService           = $lpaApplicationService->getAuthenticationService();

        $apiClient = $container->get(ApiClient::class);
        $config    = $container->get('config');

        $emailConfig = $config['email'] ?? [];
        $notifyKey   = $emailConfig['notify']['key'] ?? null;

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
                public function send(MailParameters $p): void
                {
                }
                public function healthcheck(): array
                {
                    return ['ok' => true, 'status' => 'ok'];
                }
            };
        }

        $helperPluginManager = new HelperPluginManager(new ServiceManager());

        $userService = new UserService($authService, $config, $mailTransport, $helperPluginManager);
        $userService->setApiClient($apiClient);
        $userService->setLogger($container->get(LoggerInterface::class));

        $middleware = new UserDetailsMiddleware($userService, $authService, $container->get(UserDetailsHolder::class));
        $middleware->setLogger($container->get(LoggerInterface::class));

        return $middleware;
    }
}
