<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckMemberStatusMiddleware implements MiddlewareInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sharedSpaceName = $this->inactiveSharedSpaceName($request);
        if ($sharedSpaceName === null) {
            return $handler->handle($request);
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/suspended.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'sharedSpaceName' => $sharedSpaceName,
                ],
            ),
        ));
    }

    private function inactiveSharedSpaceName(ServerRequestInterface $request): ?string
    {
        $routeName = $request->getAttribute(RouteResult::class)->getMatchedRouteName();
        if ($routeName !== 'shared-space' && !str_starts_with($routeName, 'shared-space.')) {
            return null;
        }

        $identity = $request->getAttribute(RequestAttribute::IDENTITY);
        if (!($identity instanceof User)) {
            return null;
        }

        if ($identity->getSharedSpaceId() === null) {
            return null;
        }

        $memberDetails = $this->sharedSpaceService->getMember($identity->id());
        if (is_null($memberDetails) || $memberDetails->isActive()) {
            return null;
        }

        return $memberDetails->getSharedSpaceName();
    }
}
