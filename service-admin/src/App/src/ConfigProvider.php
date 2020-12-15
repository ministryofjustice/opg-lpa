<?php

declare(strict_types=1);

namespace App;

use App\Logging\LoggingErrorListenerDelegatorFactory;
use Tuupola\Middleware\JwtAuthentication;
use Laminas\Stratigility\Middleware\ErrorHandler;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
            'rbac'         => include __DIR__ . '/../../../config/rbac.php',
            'plates'       => [
                'extensions' => [
                    View\ErrorMapper\ErrorMapperPlatesExtension::class,
                ],
            ],
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies() : array
    {
        return [
            'invokables' => [
                //  Handlers
                Handler\HomeHandler::class      => Handler\HomeHandler::class,
                Handler\SignOutHandler::class   => Handler\SignOutHandler::class,

                //  Middleware
                Middleware\Flash\SlimFlashMiddleware::class => Middleware\Flash\SlimFlashMiddleware::class,
                Middleware\Session\CsrfMiddleware::class    => Middleware\Session\CsrfMiddleware::class,
            ],
            'factories' => [
                //  Handlers
                Handler\FeedbackHandler::class      => Handler\FeedbackHandlerFactory::class,
                Handler\SignInHandler::class        => Handler\SignInHandlerFactory::class,
                Handler\SystemMessageHandler::class => Handler\SystemMessageHandlerFactory::class,
                Handler\UserSearchHandler::class    => Handler\UserSearchHandlerFactory::class,
                Handler\UserFindHandler::class      => Handler\UserFindHandlerFactory::class,

                //  Middleware
                JwtAuthentication::class                                => Middleware\Session\JwtAuthenticationFactory::class,
                Middleware\Authorization\AuthorizationMiddleware::class => Middleware\Authorization\AuthorizationMiddlewareFactory::class,
                Middleware\Session\SessionMiddleware::class             => Middleware\Session\SessionMiddlewareFactory::class,
                Middleware\ViewData\ViewDataMiddleware::class           => Middleware\ViewData\ViewDataMiddlewareFactory::class,

                //  Services
                Service\Cache\Cache::class                          => Service\Cache\CacheFactory::class,
                Service\ApiClient\Client::class                     => Service\ApiClient\ClientFactory::class,
                Service\Authentication\AuthenticationService::class => Service\Authentication\AuthenticationServiceFactory::class,
                Service\Feedback\FeedbackService::class             => Service\Feedback\FeedbackServiceFactory::class,
                Service\User\UserService::class                     => Service\User\UserServiceFactory::class,
            ],
            'initializers' => [
                Handler\Initializers\TemplatingSupportInitializer::class,
                Handler\Initializers\UrlHelperInitializer::class,
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
     */
    public function getTemplates() : array
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
