<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\UserSearch;
use App\Service\UserSearch\UserSearch as UserSearchService;
use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class UserSearchHandler extends AbstractHandler
{
    use JwtTrait;

    /**
     * @var UserSearchService
     */
    private $userSearchService;

    /**
     * UserSearchHandler constructor.
     * @param UserSearchService $userSearchService
     */
    public function __construct(UserSearchService $userSearchService)
    {
        $this->userSearchService = $userSearchService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $form = new UserSearch([
            'csrf' => $this->getTokenData('csrf'),
        ]);

        $user = null;

        if ($request->getMethod() == 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                //  Get the data from the form
                $email = $form->get('email')->getValue();

                $result = $this->userSearchService->search($email);

                if ($result === false) {
                    // Set error message
                    $messages = array_merge($form->getMessages(), [
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
