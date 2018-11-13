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
                Handler\SystemMessageHandler::class => Handler\SystemMessageHandler::class,
                Handler\UserFeedbackHandler::class  => Handler\UserFeedbackHandler::class,
                Handler\UserSearchHandler::class    => Handler\UserSearchHandler::class,
            ],
            'factories' => [
                //  Handlers
                Handler\SignInHandler::class  => Handler\SignInHandlerFactory::class,
                Handler\SignOutHandler::class => Handler\SignOutHandlerFactory::class,

                //  Middleware
                JwtAuthentication::class                      => Middleware\Auth\JwtAuthenticationFactory::class,
                Middleware\ViewData\ViewDataMiddleware::class => Middleware\ViewData\ViewDataMiddlewareFactory::class,
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
                'app'    => [__DIR__ . '/../templates/app'],
                'error'  => [__DIR__ . '/../templates/error'],
                'layout' => [__DIR__ . '/../templates/layout'],
            ],
        ];
    }
}
