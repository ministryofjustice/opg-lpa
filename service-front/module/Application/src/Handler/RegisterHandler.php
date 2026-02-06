<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Form\User\Registration;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class RegisterHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $ga = $queryParams['_ga'] ?? null;

        // Check if user is coming from gov.uk (not allowed to point directly at this page)
        $referer = $request->getHeaderLine('Referer');
        if (!empty($referer) && stripos($referer, 'www.gov.uk') !== false) {
            $redirectUrl = '/';
            if ($ga) {
                $redirectUrl .= '?_ga=' . urlencode($ga);
            }
            return new RedirectResponse($redirectUrl);
        }

        // Prevent authenticated users from accessing registration
        $identity = $request->getAttribute('identity');
        if ($identity !== null) {
            $this->logger->info('Authenticated user attempted to access registration page');
            return new RedirectResponse('/user/dashboard');
        }

        /** @var Registration $form */
        $form = $this->formElementManager->get(Registration::class);
        $form->setAttribute('action', '/signup');

        $data = [
            'form' => $form,
        ];

        // Handle POST request
        if ($request->getMethod() === 'POST') {
            $postData = $request->getParsedBody();
            $form->setData($postData);

            if ($form->isValid()) {
                $formData = $form->getData(FormInterface::VALUES_AS_ARRAY);
                $email = $formData['email'];
                $password = $formData['password'];

                $result = $this->userService->registerAccount($email, $password);

                if ($result === true || $result === "address-already-registered") {
                    // Set up form for resend email functionality
                    /** @var FormInterface $resendForm */
                    $resendForm = $this->formElementManager->get('Application\Form\User\ConfirmEmail');

                    $resendForm->setAttribute('action', '/signup/resend-email');
                    $resendForm->setData([
                        'email'         => $email,
                        'email_confirm' => $email,
                    ]);

                    return new HtmlResponse($this->renderer->render(
                        'application/general/register/email-sent.twig',
                        [
                            'form' => $resendForm,
                            'email' => $email,
                        ]
                    ));
                } else {
                    $data['error'] = $result;
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/general/register/index.twig',
            $data
        ));
    }
}
