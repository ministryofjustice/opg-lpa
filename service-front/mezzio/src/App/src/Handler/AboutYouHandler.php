<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use Application\Model\Service\User\Details as UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AboutYouHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    private const SESSION_KEY_IDENTITY = 'identity';
    private const SESSION_KEY_USER_DETAILS = 'user_details';
    private const FLASH_KEY_SUCCESS = 'flash_success';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface || !$session->has(self::SESSION_KEY_IDENTITY)) {
            return new RedirectResponse('/login');
        }

        $isNew = $request->getAttribute('new') !== null;

        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\AboutYou');

        $actionTarget = $isNew ? '/user/about-you/new' : '/user/about-you';
        $form->setAttribute('action', $actionTarget);

        $userDetails = $session->get(self::SESSION_KEY_USER_DETAILS);
        $userDetailsArr = is_array($userDetails) ? $userDetails : [];

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

                // Clear old details from session
                $session->unset(self::SESSION_KEY_USER_DETAILS);

                if (!$isNew) {
                    $session->set(self::FLASH_KEY_SUCCESS, ['Your details have been updated.']);
                }

                return new RedirectResponse('/user/dashboard');
            }
        } else {
            if (!$isNew && empty($userDetailsArr['name'])) {
                return new RedirectResponse('/user/about-you/new');
            }

            if (!empty($userDetailsArr['dob'])) {
                $dob = $userDetailsArr['dob'];
                if (is_string($dob)) {
                    $date = new \DateTime($dob);
                    $userDetailsArr['dob-date'] = [
                        'day' => $date->format('d'),
                        'month' => $date->format('m'),
                        'year' => $date->format('Y'),
                    ];
                }
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
