<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\UserSearch;
use App\Service\User\UserService;
use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class UserSearchHandler extends AbstractHandler
{
    use JwtTrait;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * UserSearchHandler constructor.
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new UserSearch([
            'csrf' => $this->getTokenData('csrf'),
        ]);

        $user = null;
        $email = null;

        if ($request->getMethod() == 'GET') {
            $params = $request->getQueryParams();

            if (isset($params['email'])) {
                $email = $params['email'];
                $form->setData(['email' => $email]);
            }
        }

        if (!is_null($email)) {
            $result = $this->userService->search($email);

            if ($result === false) {
                $formMessages = $form->getMessages();

                // Set error message
                $messages = array_merge($formMessages, [
                    'email' => [
                        'No user found for email address'
                    ]
                ]);

                $form->setMessages($messages);
            } else {
                $user = $result;
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user-search', [
            'form'  => $form,
            'user'  => $user,
        ]));
    }
}
