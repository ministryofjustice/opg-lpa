<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ManageSharedSpaceHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly SharedSpaceService $sharedSpaceService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $identity */
        $identity = $request->getAttribute(RequestAttribute::IDENTITY);

        $result = $this->sharedSpaceService->getMembers();
        $members = $result['members'];

        foreach ($members as $key => $member) {
            if ($member['id'] === $identity->id()) {
                $members[$key]['isMe'] = true;
                break;
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/manage.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'members' => $members,
                ],
            ),
        ));
    }
}
