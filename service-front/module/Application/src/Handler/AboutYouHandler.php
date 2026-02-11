<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AboutYouHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
        private readonly SessionUtility $sessionUtility,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Check user is authenticated
        $identity = $this->authenticationService->getIdentity();
        if ($identity === null) {
            return new RedirectResponse('/login');
        }

        $routeMatch = $request->getAttribute(RouteMatch::class);
        $isNew = $routeMatch?->getParam('new') !== null;

        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\AboutYou');

        $actionTarget = $isNew ? '/user/about-you/new' : '/user/about-you';
        $form->setAttribute('action', $actionTarget);

        // Get existing user details
        $userDetails = $this->userService->getUserDetails();
        $userDetailsArr = $userDetails->flatten();

        if (strtoupper($request->getMethod()) === 'POST') {
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
                $this->sessionUtility->unsetInMvc(ContainerNamespace::USER_DETAILS, 'user');

                if (!$isNew) {
                    $this->flashMessenger->addSuccessMessage('Your details have been updated.');
                }

                return new RedirectResponse('/user/dashboard');
            }
        } else {
            if (!$isNew && $userDetails->name === null) {
                return new RedirectResponse('/user/about-you/new');
            }

            if ($userDetails->dob !== null) {
                $dob = $userDetails->dob->date;

                $userDetailsArr['dob-date'] = [
                    'day' => $dob->format('d'),
                    'month' => $dob->format('m'),
                    'year' => $dob->format('Y'),
                ];
            }

            $form->bind($userDetailsArr);
        }

        $cancelUrl = '/user/dashboard';

        $html = $this->renderer->render(
            'application/authenticated/about-you/index.twig',
            [
                'form' => $form,
                'isNew' => $isNew,
                'cancelUrl' => $cancelUrl,
            ]
        );

        return new HtmlResponse($html);
    }
}
