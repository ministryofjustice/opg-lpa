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
            $searchType = $form->get('searchType')->getValue();

            if ($email !== null && $form->isValid()) {
                $input = trim($email);

                $result = match ($searchType) {
                    'userId'     => $this->userService->searchById($input),
                    'aReference' => $this->userService->searchByAReference($input),
                    default      => $this->userService->search($input),
                };

                if ($result === false) {
                    $formMessages = $form->getMessages();

                    $notFoundMessage = match ($searchType) {
                        'userId'     => 'No user found for user ID',
                        'aReference' => 'No user found for A Reference',
                        default      => 'No user found for email address',
                    };

                    // Set error message
                    $messages = array_merge($formMessages, [
                        'email' => [
                            $notFoundMessage
                        ]
                    ]);

                    $form->setMessages($messages);
                } else {
                    $user = $result;

                    $this->auditLog(
                        $request->getAttribute('user')->id,
                        'admin.user.search',
                        'Admin viewed user data',
                        ['searched_for' => $input],
                    );
                }
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user-search', [
            'form'  => $form,
            'user'  => $user,
        ]));
    }
}
