<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\UserDetails as UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmRegistrationHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Safelinks and similar prefetch the emailed link, which would otherwise consume the
        // activation before the person ever opens it. The route is GET-only, so a HEAD is
        // re-dispatched as GET by ImplicitHeadMiddleware and $request->getMethod() reports
        // 'GET' here — the original verb survives only in this attribute.
        $attribute = ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE;

        if ($request->getAttribute($attribute) === RequestMethodInterface::METHOD_HEAD) {
            return new Response();
        }

        $token = $request->getAttribute('token');

        $data = [];

        if (empty($token)) {
            $data['error'] = 'invalid-token';
            return new HtmlResponse($this->renderer->render(
                'application/general/register/confirm.twig',
                $data
            ));
        }

        // Clear any existing session
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if ($session instanceof SessionInterface) {
            $session->clear();
            $session->regenerate();
        }

        // Activate the account
        $result = $this->userService->activateAccount($token);

        if ($result === 'already-activated') {
            $data['error'] = 'already-activated';
        } elseif ($result !== true) {
            $data['error'] = 'account-missing';
        }

        return new HtmlResponse($this->renderer->render(
            'application/general/register/confirm.twig',
            $data
        ));
    }
}
