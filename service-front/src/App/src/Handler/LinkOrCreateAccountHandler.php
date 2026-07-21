<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\LinkOrCreateAccountForm;
use App\Middleware\CsrfValidationMiddleware;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LinkOrCreateAccountHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

        /** @var LinkOrCreateAccountForm $form */
        $form = $this->formElementManager->get(LinkOrCreateAccountForm::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $redirectUrl = $form->get('choice')->getValue() === 'link'
                    ? 'TODO-link-account'
                    : 'TODO-create-account';

                return new RedirectResponse($redirectUrl);
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/linking/link-or-create-account.twig',
            [
                'form' => $form,
                'csrfToken' => $csrfToken,
            ],
        ));
    }
}
