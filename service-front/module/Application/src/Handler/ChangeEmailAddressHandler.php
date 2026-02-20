<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Listener\Attribute;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Application\Form\User\ChangeEmailAddress as ChangeEmailAddressForm;

class ChangeEmailAddressHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = $this->authenticationService->getIdentity();
        if ($identity === null) {
            return new RedirectResponse('/login');
        }

        /** @var ChangeEmailAddressForm $form */
        $form = $this->formElementManager->get('Application\Form\User\ChangeEmailAddress');
        $form->setAttribute('action', '/user/change-email-address');

        $error = null;

        // Get current email from user details (set by UserDetailsListener middleware)
        $userDetails = $request->getAttribute(Attribute::USER_DETAILS);
        $currentEmailAddress = (string) $userDetails->email;

        // This form needs to check the user's current password, thus we pass it the Authentication Service
        $this->authenticationService->setEmail($currentEmailAddress);
        $form->setAuthenticationService($this->authenticationService);

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $validated = $form->getData();

                $newEmailAddress = '';
                if (is_array($validated) && isset($validated['email']) && is_string($validated['email'])) {
                    $newEmailAddress = $validated['email'];
                }

                $result = $this->userService->requestEmailUpdate($newEmailAddress, $currentEmailAddress);

                if ($result === true) {
                    $html = $this->renderer->render(
                        'application/authenticated/change-email-address/email-sent.twig',
                        array_merge(
                            $this->getTemplateVariables($request),
                            ['email' => $newEmailAddress]
                        )
                    );

                    return new HtmlResponse($html);
                } else {
                    $error = $result;
                }
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/change-email-address/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                    'error' => $error,
                    'currentEmailAddress' => $currentEmailAddress,
                    'cancelUrl' => '/user/about-you',
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
