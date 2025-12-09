<?php

declare(strict_types=1);

namespace Application\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment as TwigEnvironment;

class FeedbackThanksHandler implements RequestHandlerInterface
{
    private TwigEnvironment $twig;

    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams   = $request->getQueryParams();
        $returnTarget  = $queryParams['returnTarget'] ?? '';

        // Matches: $returnTarget = urldecode($this->params()->fromQuery('returnTarget'));
        $returnTarget = urldecode((string) $returnTarget);

        // Matches: if (empty($returnTarget)) { $returnTarget = $this->url()->fromRoute('home'); }
        if (empty($returnTarget)) {
            // No UrlHelper yet – just send them "home" at '/'
            $returnTarget = '/';
        }

        $html = $this->twig->render(
            'application/general/feedback/thanks.twig',
            [
                'returnTarget' => $returnTarget,
            ]
        );

        return new HtmlResponse($html);
    }
}
