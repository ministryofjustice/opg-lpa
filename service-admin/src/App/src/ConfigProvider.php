<?php

declare(strict_types=1);

namespace App;

use App\Logging\LoggingErrorListenerDelegatorFactory;
use Laminas\Stratigility\Middleware\ErrorHandler;
use MakeShared\Logging\LoggerFactory;
use MakeShared\Logging\LoggerRequestContextMiddleware;
use MakeShared\Logging\LoggerRequestContextMiddlewareFactory;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Session\Ext\PhpSessionPersistence;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionPersistenceInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 *
 * As this class is magically used by Laminas, psalm doesn't think it's
 * used. Suppress this misunderstanding.
 * @psalm-suppress UnusedClass
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
            'rbac'         => include __DIR__ . '/../../../config/rbac.php',
            'plates'       => [
                'extensions' => [
                    View\ErrorMapper\ErrorMapperPlatesExtension::class,
                    View\DateFormatter\DateFormatterPlatesExtension::class,
                ],
            ],
        ];
    }

    /**
     * Returns the container dependencies
     *
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
                //  Handlers
                Handler\HomeHandler::class => Handler\HomeHandler::class,
                Handler\SignOutHandler::class => Handler\SignOutHandler::class,

                //  Middleware
                Middleware\Session\CsrfMiddleware::class => Middleware\Session\CsrfMiddleware::class,
                FlashMessageMiddleware::class => FlashMessageMiddleware::class,
                SessionPersistenceInterface::class => PhpSessionPersistence::class,
            ],
            'factories' => [
                //  Handlers
                Handler\FeedbackHandler::class => Handler\FeedbackHandlerFactory::class,
                Handler\SignInHandler::class => Handler\SignInHandlerFactory::class,
                Handler\SystemMessageHandler::class => Handler\SystemMessageHandlerFactory::class,
                Handler\UserSearchHandler::class => Handler\UserSearchHandlerFactory::class,
                Handler\UserFindHandler::class => Handler\UserFindHandlerFactory::class,
                Handler\UserLpasHandler::class => Handler\UserLpasHandlerFactory::class,

                SessionMiddleware::class => function ($c) {
                    return new SessionMiddleware($c->get(SessionPersistenceInterface::class));
                },

                //  Middleware
                Middleware\Session\JwtMiddleware::class => Middleware\Session\JwtMiddlewareFactory::class,
                Middleware\Authorization\AuthorizationMiddleware::class => Middleware\Authorization\AuthorizationMiddlewareFactory::class,
                Middleware\Session\SessionMiddleware::class => Middleware\Session\SessionMiddlewareFactory::class,
                Middleware\ViewData\ViewDataMiddleware::class => Middleware\ViewData\ViewDataMiddlewareFactory::class,
                LoggerInterface::class => LoggerFactory::class,
                LoggerRequestContextMiddleware::class => LoggerRequestContextMiddlewareFactory::class,

                //  Services
                Service\Cache\Cache::class  => Service\Cache\CacheFactory::class,
                Service\ApiClient\Client::class => Service\ApiClient\ClientFactory::class,
                Service\Authentication\AuthenticationService::class => Service\Authentication\AuthenticationServiceFactory::class,
                Service\Feedback\FeedbackService::class => Service\Feedback\FeedbackServiceFactory::class,
                Service\User\UserService::class => Service\User\UserServiceFactory::class,

            ],
            'initializers' => [
                Handler\Initializers\TemplatingSupportInitializer::class,
                Handler\Initializers\UrlHelperInitializer::class,
                function (ContainerInterface $container, $instance) {
                    if ($instance instanceof LoggerAwareInterface) {
                        $instance->setLogger($container->get(LoggerInterface::class));
                    }
                }
            ],
            'delegators' => [
                ErrorHandler::class => [
                    LoggingErrorListenerDelegatorFactory::class,
                ],
            ],
        ];
    }

    /**
     * Returns the templates configuration
     *
     * @return array<string, array>
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'app'     => [__DIR__ . '/../templates/app'],
                'error'   => [__DIR__ . '/../templates/error'],
                'layout'  => [__DIR__ . '/../templates/layout'],
                'snippet' => [__DIR__ . '/../templates/snippet'],
            ],
        ];
    }
}
