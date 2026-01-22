<?php

declare(strict_types=1);

namespace App\Middleware\ViewData;

use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Plates\PlatesRenderer;

class ViewDataMiddleware implements MiddlewareInterface
{
    public function __construct(private PlatesRenderer $renderer, private string $dockerTag)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');

        $this->renderer->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'dockerTag', $this->dockerTag);
        $this->renderer->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'user', $user);
        $this->renderer->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'currentPath', $request->getUri()->getPath());

        $flashOutput = [];
        $flashAttr = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        if ($flashAttr instanceof FlashMessagesInterface) {
            $flash = $flashAttr->getFlashes();
            foreach ($flash as $type => $messages) {
                $flashOutput[$type] = is_array($messages) ? array_values($messages) : [$messages];
            }
        }

        $this->renderer->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'flash', $flashOutput);


        return $handler->handle($request);
    }
}
