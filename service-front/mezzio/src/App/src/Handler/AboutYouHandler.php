<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\RequestAttribute;
use App\Service\UserDetails as UserService;
use App\View\Twig\FlashMessenger;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use MakeShared\DataModel\User\User as UserModel;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AboutYouHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface || !$session->has('identity')) {
            return new RedirectResponse('/login');
        }

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $isNew = $request->getAttribute('new') !== null;

        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\AboutYou');

        $actionTarget = $isNew ? '/user/about-you/new' : '/user/about-you';
        $form->setAttribute('action', $actionTarget);

        // UserDetailsMiddleware fetches this from the API and sets it on every request
        /** @var UserModel|null $userDetails */
        $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);
        $userDetailsArr = $userDetails instanceof UserModel ? $userDetails->flatten() : [];

        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            // Merge existing data that doesn't change in the form
            $existingData = array_intersect_key($userDetailsArr, array_flip(['id', 'createdAt', 'updatedAt']));
            $form->setData(array_merge($data, $existingData));

            if ($form->isValid()) {
                $this->userService->updateAllDetails($form->getData());

                if (!$isNew) {
                    $flash->flash(FlashMessenger::SUCCESS, ['Your details have been updated.']);
                }

                return new RedirectResponse('/user/dashboard');
            }
        } else {
            if (!$isNew && ($userDetails === null || $userDetails->name === null)) {
                return new RedirectResponse('/user/about-you/new');
            }

            if ($userDetails instanceof UserModel && $userDetails->dob !== null) {
                $dob = $userDetails->dob->date;
                $userDetailsArr['dob-date'] = [
                    'day'   => $dob->format('d'),
                    'month' => $dob->format('m'),
                    'year'  => $dob->format('Y'),
                ];
            }

            $form->setData($userDetailsArr);
        }

        $html = $this->renderer->render(
            'application/authenticated/about-you/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'      => $form,
                    'isNew'     => $isNew,
                    'cancelUrl' => '/user/dashboard',
                    'csrfToken' => $csrfToken,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
