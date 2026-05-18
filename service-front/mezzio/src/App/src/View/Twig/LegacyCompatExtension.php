<?php

declare(strict_types=1);

namespace App\View\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides minimal stand-ins for the Twig functions/filters that the legacy
 * service-front templates depend on (asset_path, identity, url, flashMessenger,
 * renderNavigation, systemMessage). As more handlers are duplicated into the
 * new Mezzio app, these stubs will gradually be replaced with proper
 * implementations - for now they return safe defaults so the duplicated home
 * page renders identically to the legacy version.
 */
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
            // Returns the authenticated user identity. We don't have auth wired
            // up in the new Mezzio app yet, so always return null (matches the
            // unauthenticated branch of home/index.twig).
            new TwigFunction('identity', fn () => null),

            // Mezzio app routes are defined directly in config/routes.php. The
            // legacy `url()` helper resolved a route name back to a path; we
            // hard-code the small set of paths the home page links to.
            new TwigFunction('url', [$this, 'url']),

            // The home page calls `flashMessenger().setMessageOpenFormat(...)`
            // chained method calls, so we return a tiny stub that swallows the
            // calls and renders nothing.
            new TwigFunction('flashMessenger', fn () => new FlashMessengerStub()),

            // Navigation bar rendered from the duplicated nav.twig partial.
            // Without session/auth wired up we pass an unauthenticated nav model.
            new TwigFunction('renderNavigation', [$this, 'renderNavigation'], ['is_safe' => ['html'], 'needs_environment' => true]),

            // System-wide message banner (e.g. planned maintenance). Off by
            // default in the new app.
            new TwigFunction('systemMessage', fn () => '', ['is_safe' => ['html']]),
        ];
    }

    /**
     * Mirrors Application\View\Twig\AppFiltersExtension::assetPath - inserts the
     * configured cache-busting segment and optional `.min` suffix into asset
     * paths so `/assets/v2/css/application.css` becomes
     * `/assets/v2/<cache>/css/application.min.css`.
     */
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
