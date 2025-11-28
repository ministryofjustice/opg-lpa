<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\UserSearch;
use App\Service\User\UserService;
use App\Handler\Traits\JwtTrait;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class UserSearchHandler extends AbstractHandler
{
    use JwtTrait;

    public function __construct(private readonly UserService $userService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new UserSearch([
            'csrf' => $this->getTokenData('csrf'),
        ]);

        $user = null;
        $email = '';

        if ($request->getMethod() === RequestMethodInterface::METHOD_GET) {
            $queryParams = $request->getQueryParams();

            if (isset($queryParams['email'])) {
                $email = (string)$queryParams['email'];
            }

            $form->setData(['email' => $email]);
        }

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $parsedBody = $request->getParsedBody();

            if (!is_array($parsedBody)) {
                $parsedBody = [];
            }

            $form->setData($parsedBody);
            $email = $form->get('email')->getValue();

            if ($email !== null && $form->isValid()) {
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
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user-search', [
            'form'  => $form,
            'user'  => $user,
        ]));
    }
}
