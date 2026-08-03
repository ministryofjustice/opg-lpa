<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\ManageSharedSpaceHandler;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ManageSharedSpaceHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private SharedSpaceService&MockObject $sharedSpaceService;
    private ManageSharedSpaceHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);

        $this->handler = new ManageSharedSpaceHandler(
            $this->renderer,
            $this->sharedSpaceService,
        );
    }

    public function testGetShowsMembers(): void
    {
        $this->sharedSpaceService
            ->expects($this->once())
            ->method('getMembers')
            ->willReturn(['members' => [
                ['id' => 'a-user'],
                ['id' => 'my-user'],
                ['id' => 'another-user'],
            ]]);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/manage.twig', [
                'members' => [
                    ['id' => 'a-user'],
                    ['id' => 'my-user', 'isMe' => true],
                    ['id' => 'another-user'],
                ],
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('GET');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
