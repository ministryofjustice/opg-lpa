<?php

declare(strict_types=1);

namespace App\Model\Service\Session;

use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;

class PersistentSessionDetails
{
    private const string SESSION_KEY = 'SessionDetails';

    private ?SessionInterface $session = null;

    /**
     * Called once per request by PersistentSessionDetailsMiddleware after routing.
     */
    public function refresh(?RouteResult $routeResult, SessionInterface $session): void
    {
        $this->session = $session;

        // breadcrumb so we can determine user's last visited route.
        // Also account for any null values, eg activation links or status checks.
        $currentRoute = ($routeResult !== null && $routeResult->isSuccess())
            ? $routeResult->getMatchedRouteName()
            : '';

        $details = $this->session->get(self::SESSION_KEY, []);

        $routeStore = $details['routeStore'] ?? null;
        $previousRoute = $details['previousRoute'] ?? null;

        if ($routeStore !== $previousRoute) {
            $details['previousRoute'] = $routeStore;
        }

        $details['currentRoute'] = $currentRoute;
        $details['routeStore'] = $currentRoute;

        $this->session->set(self::SESSION_KEY, $details);
    }

    public function getCurrentRoute(): string
    {
        $details = $this->session?->get(self::SESSION_KEY, []) ?? [];
        return $details['currentRoute'] ?? '';
    }

    public function getPreviousRoute(): string
    {
        $details = $this->session?->get(self::SESSION_KEY, []) ?? [];
        return $details['previousRoute'] ?? 'home';
    }
}
