<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteStackInterface;

trait RedirectTrait
{
    private ?RouteStackInterface $redirectRouter = null;

    /**
     * @return MvcEvent
     */
    abstract public function getEvent();

    protected function getRedirectRouter(): RouteStackInterface
    {
        if ($this->redirectRouter === null) {
            $event = $this->getEvent();
            $application = $event->getApplication();

            if ($application === null) {
                throw new \RuntimeException(
                    'Application not available. In tests, call setRedirectRouter() on the controller.'
                );
            }

            $this->redirectRouter = $application
                ->getServiceManager()
                ->get(RouteStackInterface::class);
        }

        return $this->redirectRouter;
    }

    public function setRedirectRouter(RouteStackInterface $router): void
    {
        $this->redirectRouter = $router;
    }

    protected function redirectToRoute(
        string $route,
        array $params = [],
        array $options = [],
        int $status = 302
    ): HttpResponse {
        return $this->redirectToUrl($this->generateUrl($route, $params, $options), $status);
    }

    protected function redirectToUrl(string $url, int $status = 302): HttpResponse
    {
        $response = new HttpResponse();
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaderLine('Location', $url);

        return $response;
    }

    protected function generateUrl(string $route, array $params = [], array $options = []): string
    {
        $options['name'] = $route;
        return $this->getRedirectRouter()->assemble($params, $options);
    }
}
