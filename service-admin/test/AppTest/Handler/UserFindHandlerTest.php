<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\UserFind;
use App\Handler\UserFindHandler;
use App\Service\User\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Name;
use PHPUnit\Framework\TestCase;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;

class UserFindHandlerTest extends TestCase
{
    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private UserService|MockObject $mockUserService;
    private UserFindHandler $handler;

    protected function setUp(): void
    {
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockUserService = $this->createMock(UserService::class);

        $_SESSION['jwt-payload'] =  ['csrf' => Common::TEST_CSRF_TOKEN];

        $this->handler = new UserFindHandler($this->mockUserService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
    }

    public function testRendersForm()
    {
        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams([]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-find',
            $this->callback(fn ($args) => $args['form'] instanceof UserFind)
        )->willReturn('response');

        $this->handler->handle($request);
    }

    public function testSubmitsSearch()
    {
        $user = new User(['name' => new Name(['first' => 'David'])]);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserFind::class);

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams([
                'query' => 'test',
                'offset' => '0',
                'secret' => $secret,
            ]);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with(['query' => 'test', 'offset' => '0', 'limit' => 11])
            ->willReturn([$user]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-find',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserFind
                && $args['form']->get('query')->getValue() === 'test'
                && $args['users'] === [$user])
        )->willReturn('response');

        $this->handler->handle($request);
    }

    public function testRequiresCsrf()
    {
        $secret = 'not_the_real_hash'; // pragma: allowlist secret

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams([
                'query' => 'test',
                'offset' => '0',
                'secret' => $secret,
            ]);

        $this->mockUserService->expects($this->never())->method('match');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-find',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserFind
                && $args['form']->getMessages('secret') === [
                    'notSame' => 'The form submitted did not originate from the expected site'
                ]
                && $args['users'] === [])
        )->willReturn('response');

        $this->handler->handle($request);
    }
}
