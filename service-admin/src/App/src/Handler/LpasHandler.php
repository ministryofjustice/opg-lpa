<?php

declare(strict_types=1);

namespace App\Handler;

use App\RequestAttributes;
use App\Service\Paginator;
use App\Service\UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * As this class is instantiated via autowiring and referenced only by class
 * name in routes.php, psalm doesn't think it's used.
 * @psalm-suppress UnusedClass
 */
class LpasHandler extends AbstractHandler
{
    public function __construct(
        private readonly UserService $userService,
        private readonly Paginator $paginator,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $sharedSpaceId = $request->getAttribute('sharedSpaceId');
        $userEmail = $request->getQueryParams()['email'] ?? null;
        $sharedSpaceName = $request->getQueryParams()['sharedSpaceName'] ?? null;

        $this->paginator->setPerPage(20);
        $this->paginator->setPage((int) ($request->getQueryParams()['page'] ?? 1));

        if (empty($userEmail) && empty($sharedSpaceName)) {
            return new HtmlResponse($this->getTemplateRenderer()->render('app::view-lpas', [
                'userId' => $userId,
                'failureReason' => 'A user email or shared space name must be provided',
            ]), 404);
        }

        $result = $sharedSpaceId
            ? $this->userService->sharedSpaceLpas($sharedSpaceId, $this->paginator->getPage(), $this->paginator->getPerPage())
            : $this->userService->userLpas($userId, $this->paginator->getPage(), $this->paginator->getPerPage());

        if ($result === false) {
            return new HtmlResponse($this->getTemplateRenderer()->render('app::view-lpas', [
                'userId' => $userId,
                'failureReason' => 'No LPAs found',
            ]), 404);
        }

        $lpas = $result['results'];
        $this->paginator->setTotal($result['total']);

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
            'routeName' => $sharedSpaceId ? 'shared-space.lpas' : 'user.lpas',
            'routeParams' => $sharedSpaceId ? ['sharedSpaceId' => $sharedSpaceId] : ['userId' => $userId],
            'queryParams' => $sharedSpaceId ? ['sharedSpaceName' => $sharedSpaceName] : ['email' => $userEmail],
            'paginator' => $this->paginator,
        ]));
    }
}
