<?php

declare(strict_types=1);

namespace App;

use Tuupola\Middleware\JwtAuthentication;

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
                Handler\HomeHandler::class          => Handler\HomeHandler::class,
                Handler\SignOutHandler::class       => Handler\SignOutHandler::class,
                Handler\SystemMessageHandler::class => Handler\SystemMessageHandler::class,
                Handler\UserFeedbackHandler::class  => Handler\UserFeedbackHandler::class,
                Handler\UserSearchHandler::class    => Handler\UserSearchHandler::class,

                //  Middleware
                Middleware\Flash\SlimFlashMiddleware::class => Middleware\Flash\SlimFlashMiddleware::class,
                Middleware\Session\CsrfMiddleware::class    => Middleware\Session\CsrfMiddleware::class,
            ],
            'factories' => [
                //  Handlers
                Handler\SignInHandler::class  => Handler\SignInHandlerFactory::class,

                //  Middleware
                JwtAuthentication::class                                => Middleware\Session\JwtAuthenticationFactory::class,
                Middleware\Authorization\AuthorizationMiddleware::class => Middleware\Authorization\AuthorizationMiddlewareFactory::class,
                Middleware\Session\SessionMiddleware::class             => Middleware\Session\SessionMiddlewareFactory::class,
                Middleware\ViewData\ViewDataMiddleware::class           => Middleware\ViewData\ViewDataMiddlewareFactory::class,

                //  Services
                Service\ApiClient\Client::class                     => Service\ApiClient\ClientFactory::class,
                Service\Authentication\AuthenticationService::class => Service\Authentication\AuthenticationServiceFactory::class,
            ],
            'initializers' => [
                Handler\Initializers\TemplatingSupportInitializer::class,
                Handler\Initializers\UrlHelperInitializer::class,
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
