<?php

declare(strict_types=1);

namespace App\Handler;

use App\RequestAttributes;
use App\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class UserLpasHandler extends AbstractHandler
{
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

        $this->auditLog(
            $request->getAttribute(RequestAttributes::USER_EMAIL),
            'admin.user.lpas.view',
            'Admin viewed user LPAs',
            [
                'viewed_user' => $userId,
                'lpa_count' => count($lpas),
            ],
        );

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user-lpas', [
            'lpaEmail' => $userEmail,
            'lpas' => $lpas,
        ]));
    }
}
