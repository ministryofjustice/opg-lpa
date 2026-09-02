<?php

declare(strict_types=1);

namespace App\Handler;

use App\RequestAttributes;
use App\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * As this class is instantiated via autowiring and referenced only by class
 * name in routes.php, psalm doesn't think it's used.
 * @psalm-suppress UnusedClass
 */
class LpasHandler extends AbstractHandler
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $sharedSpaceId = $request->getAttribute('sharedSpaceId');
        $userEmail = $request->getQueryParams()['email'] ?? null;
        $sharedSpaceName = $request->getQueryParams()['sharedSpaceName'] ?? null;

        if (empty($userEmail) && empty($sharedSpaceName)) {
            return new HtmlResponse($this->getTemplateRenderer()->render('app::view-lpas', [
                'userId' => $userId,
                'failureReason' => 'A user email or shared space name must be provided',
            ]), 404);
        }

        $lpas = $sharedSpaceId ? $this->userService->sharedSpaceLpas($sharedSpaceId) : $this->userService->userLpas($userId);

        if ($lpas === false) {
            return new HtmlResponse($this->getTemplateRenderer()->render('app::view-lpas', [
                'userId' => $userId,
                'failureReason' => 'No LPAs found',
            ]), 404);
        }

        $this->auditLog(
            $request->getAttribute(RequestAttributes::USER_EMAIL),
            'admin.user.lpas.view',
            'Admin viewed user LPAs',
            [
                'viewed_user' => $userId,
                'lpa_count' => count($lpas),
                'shared_space_id' => $sharedSpaceId,
            ],
        );

        return new HtmlResponse($this->getTemplateRenderer()->render('app::view-lpas', [
            'lpasOwner' => $userEmail ?: $sharedSpaceName,
            'lpas' => $lpas,
        ]));
    }
}
