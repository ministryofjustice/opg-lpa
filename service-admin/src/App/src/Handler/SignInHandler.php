<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\SignIn;
use App\Handler\Traits\JwtTrait;
use App\User\User;
use App\Service\Authentication\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * Class SignInHandler
 * @package App\Handler
 */
class SignInHandler extends AbstractHandler
{
    use JwtTrait;

    /**
     * @var AuthenticationService
     */
    private $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $data = [];

//TODO - Change this...
        $token = $this->getTokenData('token');

        if (!is_null($token)) {
            return $this->redirectToRoute('home');
        }

        $form = new SignIn([
            'csrf' => $this->getTokenData('csrf'),
        ]);

        if ($request->getMethod() == 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                //  Get the data from the form and authenticate with the service
                $email = $form->get('email')->getValue();
                $password = $form->get('password')->getValue();

                $result = $this->authService->authenticate($email, $password);

                if ($result->isValid()) {
                    //  Update the JWT data with the user data
                    /** @var User $user */
                    $user = $result->getIdentity();

                    $this->addTokenData('token', $user->getToken());

                    return $this->redirectToRoute('home');
                }

                if ($result->getCode() === Result::FAILURE_ACCOUNT_LOCKED) {
                    //  Reset the password and set the token in the email to the user
                    $user = $this->userService->resetPassword($email);

                    //  Generate the change password URL
                    $host = sprintf('%s://%s', $request->getUri()->getScheme(), $request->getUri()->getAuthority());

                    $changePasswordUrl = $host . $this->getUrlHelper()->generate('password.change', [
                            'token' => $user->getToken(),
                        ]);

                    //  Send the set password email to the new user
                    $this->notifyClient->sendEmail($email, '3346472f-a804-496c-b443-af317f4b16a5', [
                        'change-password-url' => $changePasswordUrl,
                    ]);

                    $form->setAuthError('account-locked');
                } else {
                    $form->setAuthError('auth-error');
                }
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::sign-in', [
            'form'     => $form,
            'messages' => $this->getFlashMessages($request)
        ]));
    }
}
