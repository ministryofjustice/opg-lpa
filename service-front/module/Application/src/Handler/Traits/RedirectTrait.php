<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteStackInterface;

trait RedirectTrait
{
    private ?RouteStackInterface $router = null;
    /**
     * @return MvcEvent
     */
    abstract public function getEvent();
    protected function getRouter(): RouteStackInterface
    {
        if ($this->router === null) {
            $this->router = $this->getEvent()
                ->getApplication()
                ->getServiceManager()
                ->get(RouteStackInterface::class);
        }
        return $this->router;
    }

    protected function redirectToRoute(
        string $route,
        array $params = [],
        array $options = [],
        int $status = 302
    ): RedirectResponse {
        return new RedirectResponse($this->generateUrl($route, $params, $options), $status);
    }

    protected function redirectToUrl(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    protected function generateUrl(string $route, array $params = [], array $options = []): string
    {
        $options['name'] = $route;
        return $this->getRouter()->assemble($params, $options);
    }
}
