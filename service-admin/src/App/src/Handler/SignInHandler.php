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
use Zend\Diactoros\Response\HtmlResponse;
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
     * @var array
     */
    private $adminUsers;

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
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
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

                //  Check to see if the user is a permitted admin user
                if (in_array($email, $this->adminUsers)) {
                    $result = $this->authService->authenticate($email, $password);

                    if ($result->isValid()) {
                        //  Update the JWT data with the user data
                        /** @var Identity $identity */
                        $identity = $result->getIdentity();
                        $this->addTokenData('token', $identity->getToken());

                        $user = $this->userService->fetch($identity->getUserId());

                        if(!isset($user->name)){
                            return new HtmlResponse($this->getTemplateRenderer()->render('error::no-user-details-error', [
                                'user'         => $user,
                            ]));
                        }
                        else {
                            return $this->redirectToRoute('home');
                        }
                    }

                    $form->setAuthError($result->getCode() === Result::FAILURE_ACCOUNT_LOCKED ? 'account-locked' : 'authentication-error');
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
