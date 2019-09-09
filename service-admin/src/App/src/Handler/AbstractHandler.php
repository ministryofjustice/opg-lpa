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
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
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
     * AbstractHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     */
    public function __construct(TemplateRendererInterface $renderer, UrlHelper $urlHelper)
    {
        $this->renderer = $renderer;
        $this->urlHelper = $urlHelper;
    }

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
