<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Initializers\TemplatingSupportInterface;
use App\Handler\Initializers\TemplatingSupportTrait;
use App\Handler\Initializers\UrlHelperInterface;
use App\Handler\Initializers\UrlHelperTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Flash\Messages;
use Laminas\Diactoros\Response;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AbstractHandler
 * @package App\Handler
 */
abstract class AbstractHandler implements RequestHandlerInterface, TemplatingSupportInterface, UrlHelperInterface
{
    use TemplatingSupportTrait;
    use UrlHelperTrait;

    //
    /** @var TemplateRendererInterface */
    protected $renderer;

    /** @var UrlHelper */
    protected $urlHelper;

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function handle(ServerRequestInterface $request): ResponseInterface;
    //

    /**
     * Redirect to the specified route
     *
     * @param string|UriInterface $route
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $queryParams
     * @return Response\RedirectResponse
     */
    protected function redirectToRoute($route, $routeParams = [], $queryParams = []): Response\RedirectResponse
    {
        return new Response\RedirectResponse(
            $this->getUrlHelper()->generate("{$route}", $routeParams, $queryParams)
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $message
     * @param bool $now
     * @return void
     */
    protected function setFlashInfoMessage(ServerRequestInterface $request, string $message, bool $now = false): void
    {
        $this->setFlashMessage($request, 'info', $message, $now);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $key
     * @param string $message
     * @param bool $now
     * @return void
     */
    protected function setFlashMessage(
        ServerRequestInterface $request,
        string $key,
        string $message,
        bool $now = false
    ): void {
        /** @var Messages $flash */
        $flash = $request->getAttribute('flash');

        if ($now) {
            $flash->addMessageNow($key, $message);
        } else {
            $flash->addMessage($key, $message);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return array<string, mixed>
     */
    protected function getFlashMessages(ServerRequestInterface $request): array
    {
        return $request->getAttribute('flash')->getMessages();
    }
}
