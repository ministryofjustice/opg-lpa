<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Initializers\TemplatingSupportInterface;
use App\Handler\Initializers\TemplatingSupportTrait;
use App\Handler\Initializers\UrlHelperInterface;
use App\Handler\Initializers\UrlHelperTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Flash\Messages;
use Zend\Diactoros\Response;

/**
 * Class AbstractHandler
 * @package App\Handler
 */
abstract class AbstractHandler implements RequestHandlerInterface, TemplatingSupportInterface, UrlHelperInterface
{
    use TemplatingSupportTrait;
    use UrlHelperTrait;

    /**
     * Redirect to the specified route
     *
     * @param $route
     * @param array $routeParams
     * @param array $queryParams
     * @return Response\RedirectResponse
     */
    protected function redirectToRoute($route, $routeParams = [], $queryParams = [])
    {
        return new Response\RedirectResponse(
            $this->getUrlHelper()->generate($route, $routeParams, $queryParams)
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param $message
     * @param bool $now
     */
    protected function setFlashInfoMessage(ServerRequestInterface $request, $message, bool $now = false)
    {
        $this->setFlashMessage($request, 'info', $message, $now);
    }

    protected function setFlashMessage(ServerRequestInterface $request, $key, $message, bool $now = false)
    {
        /** @var Messages $flash */
        $flash = $request->getAttribute('flash');

        if ($now) {
            $flash->addMessageNow($key, $message);
        } else {
            $flash->addMessage($key, $message);
        }
    }

    protected function getFlashMessages(ServerRequestInterface $request)
    {
        /** @var Messages $flash */
        $flash = $request->getAttribute('flash');
        return $flash->getMessages();
    }
}
