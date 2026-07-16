<?php

declare(strict_types=1);

namespace App;

use App\Logging\LoggingErrorListenerDelegatorFactory;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;
use MakeShared\Logging\LoggerFactory;
use MakeShared\Logging\RequestLoggingMiddleware;
use MakeShared\Logging\RequestLoggingMiddlewareFactory;
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
                Handler\SignInHandler::class => Handler\SignInHandler::class,

                //  Middleware
                Middleware\Session\CsrfMiddleware::class => Middleware\Session\CsrfMiddleware::class,
                FlashMessageMiddleware::class => FlashMessageMiddleware::class,
                SessionPersistenceInterface::class => PhpSessionPersistence::class,
            ],
            'aliases' => [
                //  Allows SystemMessageHandler's StorageInterface dependency to be autowired
                //  to the concrete Cache service.
                StorageInterface::class => Service\Cache\Cache::class,
            ],
            'factories' => [
                //  Handlers
                //  Note: FeedbackHandler, SystemMessageHandler, UserFindHandler,
                //  UserLpasHandler and UserSearchHandler are autowired by laminas/laminas-di
                //  as their constructors only depend on other container-known services.
                Handler\SignOutHandler::class => Handler\SignOutHandlerFactory::class,

                SessionMiddleware::class => function ($c) {
                    return new SessionMiddleware($c->get(SessionPersistenceInterface::class));
                },

                //  Middleware
                Middleware\Authorization\AlbOidcMiddleware::class => Middleware\Authorization\AlbOidcMiddlewareFactory::class,
                Middleware\Authorization\AlbSimulatorMiddleware::class => Middleware\Authorization\AlbSimulatorMiddlewareFactory::class,
                Middleware\Authorization\AuthorizationMiddleware::class => Middleware\Authorization\AuthorizationMiddlewareFactory::class,
                Middleware\ViewData\ViewDataMiddleware::class => Middleware\ViewData\ViewDataMiddlewareFactory::class,
                LoggerInterface::class => LoggerFactory::class,
                RequestLoggingMiddleware::class => RequestLoggingMiddlewareFactory::class,

                //  Services
                //  Note: AuthenticationService, FeedbackService and UserService are autowired
                //  by laminas/laminas-di as their constructors only depend on the ApiClient service.
                Service\Cache\Cache::class  => Service\Cache\CacheFactory::class,
                Service\ApiClient\Client::class => Service\ApiClient\ClientFactory::class,
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
