<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\User\UserService;
use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class UserFindHandler extends AbstractHandler
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
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $users = $this->userService->match(['query' => 'digital', 'offset' => 0, 'limit' => 10]);

        // TODO test datetime display is correct (see UserSearchHandler)

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user-find', [
            'users' => $users
        ]));
    }
}
