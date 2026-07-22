<?php

declare(strict_types=1);

namespace App\Handler;

use App\Authentication\AuthenticationService;
use App\Form\User\Login;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\UserDetails;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LinkAccountHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly AuthenticationService $authenticationService,
        private readonly UserDetails $userDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

        /** @var Login $form */
        $form = $this->formElementManager->get(Login::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $result = $this->authenticationService
                    ->setEmail($form->get('email')->getValue())
                    ->setPassword($form->get('password')->getValue())
                    ->authenticate();

                if ($result->isValid() && $this->userDetails->setOneLoginSub('TODO-get-the-current-one-login-sub')) {
                    return new RedirectResponse('/user/dashboard');
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/linking/link-account.twig',
            [
                'form' => $form,
                'csrfToken' => $csrfToken,
            ],
        ));
    }
}
