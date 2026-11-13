<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Service\SharedSpace\SharedSpaceService;
use App\View\Twig\FlashMessenger;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteSharedSpaceMemberHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly SharedSpaceService $sharedSpaceService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        $memberId = $routeResult?->getMatchedParams()['member-id'] ?? null;

        $member = $this->sharedSpaceService->getMember($memberId);
        if ($member === null) {
            return new RedirectResponse('/shared-space');
        }

        $error = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            if ($this->sharedSpaceService->deleteMember($member)) {
                /** @var FlashMessagesInterface $flash */
                $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                $name = $member->getName();
                $flash->flash(FlashMessenger::SUCCESS, [$name->getFirst() . ' ' . $name->getLast() . ' has been removed.']);

                return new RedirectResponse('/shared-space');
            }

            $error = 'Failed to delete member. Please try again.';
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/delete-member.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'member'    => $member,
                    'error'     => $error,
                ],
            ),
        ));
    }
}
