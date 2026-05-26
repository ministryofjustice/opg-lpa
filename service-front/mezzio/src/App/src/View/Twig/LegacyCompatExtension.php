<?php

declare(strict_types=1);

namespace App\View\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LegacyCompatExtension extends AbstractExtension
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('asset_path', [$this, 'assetPath']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('identity', fn () => null),
            new TwigFunction('url', [$this, 'url']),
            new TwigFunction('flashMessenger', fn () => new FlashMessengerStub()),
            new TwigFunction('renderNavigation', [$this, 'renderNavigation'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new TwigFunction('systemMessage', fn () => '', ['is_safe' => ['html']]),
        ];
    }

    public function assetPath(string $path, array $options = []): string
    {
        $cache = $this->config['version']['cache'] ?? '';

        if ($cache !== '') {
            $path = str_replace('/assets/', "/assets/{$cache}/", $path);
        }

        if (isset($options['minify']) && $options['minify'] === true) {
            $lastDot = strrpos($path, '.');
            if ($lastDot !== false) {
                $path = substr($path, 0, $lastDot) . '.min' . substr($path, $lastDot);
            }
        }

        return $path;
    }

    public function url(string $routeName, array $params = []): string
    {
        return match ($routeName) {
            'register' => '/register',
            'login'    => '/login',
            'logout'   => '/logout',
            'user/about-you' => '/user/about-you',
            'user/dashboard' => '/user/dashboard',
            'user/dashboard/create-lpa' => '/user/dashboard/create-lpa',
            default    => '/' . ltrim($routeName, '/'),
        };
    }

    /**
     * Renders the service navigation partial. Without session/auth wired up
     * we always render as an unauthenticated user.
     */
    public function renderNavigation(Environment $env, string $currentRoute = ''): string
    {
        return $env->render('application/partials/nav.twig', [
            'nav' => (object) [
                'userLoggedIn'    => false,
                'name'            => '',
                'lastLoginAt'     => null,
                'route'           => $currentRoute,
                'hasOneOrMoreLPAs' => false,
            ],
        ]);
    }
}
