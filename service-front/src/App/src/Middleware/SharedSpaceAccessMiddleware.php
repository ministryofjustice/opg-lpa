<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Model\Service\Authentication\Identity\User;
use App\Model\UserDetailsHolder;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SharedSpaceAccessMiddleware implements MiddlewareInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly TemplateRendererInterface $renderer,
        private readonly UserDetailsHolder $userDetailsHolder,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sharedSpaceAllow = $this->sharedSpaceRoute($request);
        if (count($sharedSpaceAllow) === 0) {
            return $handler->handle($request);
        }

        $userClasses = $this->classifyUser($request);

        if (isset($userClasses['suspendedFrom'])) {
            return new HtmlResponse($this->renderer->render(
                'application/authenticated/shared-space/suspended.twig',
                array_merge(
                    $this->getTemplateVariables($request),
                    [
                        'sharedSpaceName' => $userClasses['suspendedFrom'],
                    ],
                ),
            ));
        }

        if (count(array_intersect($sharedSpaceAllow, $userClasses)) > 0) {
            return $handler->handle($request);
        }

        return new RedirectResponse(
            array_intersect(['member', 'admin'], $userClasses)
                ? '/shared-space/dashboard'
                : '/user/dashboard'
        );
    }

    /**
     * @return array<'professional'|'invited'|'member'|'admin'>
     */
    private function sharedSpaceRoute(ServerRequestInterface $request): array
    {
        /** @var Route $route */
        $route = $request->getAttribute(RouteResult::class)->getMatchedRoute();

        return $route->getOptions()['sharedSpaceAllow'] ?? [];
    }

    /**
     * @return array<'admin'|'member'>|array<'professional'|'invited'>|array{suspendedFrom: string}
     */
    private function classifyUser(ServerRequestInterface $request): array
    {
        $classes = [];

        $identity = $request->getAttribute(RequestAttribute::IDENTITY);
        if ($identity instanceof User && $identity->inSharedSpace()) {
            $memberDetails = $this->sharedSpaceService->getMember($identity->id());
            if (is_null($memberDetails)) {
                return [];
            }

            if (!$memberDetails->isActive()) {
                return ['suspendedFrom' => $memberDetails->getSharedSpaceName()];
            }

            $classes [] = 'member';
            if ($memberDetails->isAdmin()) {
                $classes [] = 'admin';
            }
        } else {
            $userDetails = $this->userDetailsHolder->get();
            if ($userDetails !== null) {
                if ($userDetails->getIsProfessional()) {
                    $classes [] = 'professional';
                }

                if ($userDetails->getIsInvited()) {
                    $classes [] = 'invited';
                }
            }
        }

        return $classes;
    }
}
