<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\User\UserService;
use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class UserLpasHandler extends AbstractHandler
{
    use JwtTrait;

    public function __construct(private readonly UserService $userService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('id');
        $userEmail = $request->getQueryParams()['email'] ?? '';

        if (!isset($request->getQueryParams()['email'])) {
            return new HtmlResponse($this->getTemplateRenderer()->render('app::user-lpas', [
                'userId' => $userId,
                'failureReason' => 'missing-email',
            ]), 404);
        }

        $lpas = $this->userService->userLpas($userId);

        if ($lpas === false) {
            return new HtmlResponse($this->getTemplateRenderer()->render('app::user-lpas', [
                'userId' => $userId,
                'failureReason' => 'no-lpas',
            ]), 404);
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user-lpas', [
            'lpaEmail' => $userEmail,
            'lpas' => $lpas,
        ]));
    }
}
