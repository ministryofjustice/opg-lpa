<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\SignIn;
use App\Handler\Traits\JwtTrait;
use App\Service\Authentication\Identity;
use App\Service\Authentication\Result;
use App\Service\Authentication\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use App\Service\User\UserService;

/**
 * Class SignInHandler
 * @package App\Handler
 */
class SignInHandler extends AbstractHandler
{
    use JwtTrait;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var AuthenticationService
     */
    private $authService;

    /**
     * @var array<string>
     */
    private $adminUsers;

    /**
     * @param AuthenticationService $authService
     * @param array<string> $adminUsers
     * @param UserService $userService
     */
    public function __construct(AuthenticationService $authService, array $adminUsers, UserService $userService)
    {
        $this->authService = $authService;
        $this->adminUsers = $adminUsers;
        $this->userService = $userService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->getTokenData('token');

        if (!is_null($token)) {
            return $this->redirectToRoute('home');
        }

        $form = new SignIn([
            'csrf' => $this->getTokenData('csrf'),
        ]);

        if ($request->getMethod() == 'POST') {
            $parsedBody = $request->getParsedBody();

            // to protect against a POST body which isn't name/value pairs
            if (!is_array($parsedBody)) {
                $parsedBody = [];
            }

            $form->setData($parsedBody);

            if ($form->isValid()) {
                //  Get the data from the form and authenticate with the service
                $email = $form->get('email')->getValue();
                $password = $form->get('password')->getValue();

                //  Check to see if the user is a permitted admin user
                if (in_array($email, $this->adminUsers)) {
                    $result = $this->authService->authenticate($email, $password);

                    if ($result->isValid()) {
                        //  Update the JWT data with the user data
                        /** @var Identity $identity */
                        $identity = $result->getIdentity();

                        $token = $identity->getToken();

                        // only save the user token if it isn't null
                        if (!is_null($token)) {
                            $this->addTokenData('token', $token);
                        }

                        // ensure we have a string for the user ID, even if it's empty
                        $user = $this->userService->fetch($identity->getUserId() ?? '');

                        if (!isset($user->name)) {
                            return new HtmlResponse(
                                $this->getTemplateRenderer()
                                    ->render('error::no-user-details-error', [
                                        'user' => $user,
                                    ])
                            );
                        } else {
                            return $this->redirectToRoute('home');
                        }
                    }

                    $form->setAuthError(
                        $result->getCode() === Result::FAILURE_ACCOUNT_LOCKED ?
                            'account-locked' : 'authentication-error'
                    );
                } else {
                    $form->setAuthError('authorization-error');
                }
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::sign-in', [
            'form'     => $form,
            'messages' => $this->getFlashMessages($request)
        ]));
    }
}
