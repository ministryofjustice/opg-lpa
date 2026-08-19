<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SharedSpaceDashboardHandler;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\Lpa\Application as LpaApplicationService;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SharedSpaceDashboardHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private LoggerInterface&MockObject $logger;
    private SharedSpaceDashboardHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new SharedSpaceDashboardHandler(
            $this->renderer,
            $this->lpaApplicationService,
            $this->logger,
        );
    }

    private function createRequest(
        int $page = 1,
        ?string $search = null,
        ?User $identity = null,
    ): ServerRequest {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['page' => $page]);

        $identity ??= $this->createMock(User::class);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(RequestAttribute::IDENTITY, $identity);

        if ($search !== null) {
            $request = $request->withQueryParams(['search' => $search]);
        }

        return $request;
    }

    public function testRendersDashboardCreateWhenNoLpas(): void
    {
        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getSharedSpaceLpaSummaries')
            ->with(null, 1, 50)
            ->willReturn([
                'name'            => 'My space',
                'applications'    => [],
                'total'           => 0,
                'trackingEnabled' => false,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('<html>dashboard</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRendersDashboardWhenLpasExist(): void
    {
        $lastLogin = new DateTime('2026-06-01 10:00:00');
        $identity = $this->createMock(User::class);
        $identity->expects($this->once())->method('lastLogin')->willReturn($lastLogin);

        $lpas = [
            ['id' => 1001, 'donor' => 'Alice Example'],
            ['id' => 1002, 'donor' => 'Bob Example'],
        ];

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getSharedSpaceLpaSummaries')
            ->with(null, 1, 50)
            ->willReturn([
                'name'            => 'My space',
                'applications'    => $lpas,
                'total'           => 2,
                'trackingEnabled' => true,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/dashboard.twig',
                $this->callback(function (array $params) use ($lpas, $lastLogin): bool {
                    return $params['lpas'] === $lpas
                        && $params['lpaTotalCount'] === 2
                        && $params['freeText'] === null
                        && $params['isSearch'] === false
                        && $params['trackingEnabled'] === true
                        && $params['user']['lastLogin'] === $lastLogin
                        && $params['paginationControlData']['page'] === 1
                        && $params['paginationControlData']['pageCount'] === 1
                        && $params['paginationControlData']['pagesInRange'] === [1]
                        && $params['sharedSpaceName'] == 'My space';
                })
            )
            ->willReturn('<html>dashboard</html>');

        $response = $this->handler->handle($this->createRequest(identity: $identity));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRendersDashboardWhenSearchHasNoResults(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('lastLogin')->willReturn(new DateTime('2026-06-01 10:00:00'));

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getSharedSpaceLpaSummaries')
            ->with('smith', 1, 50)
            ->willReturn([
                'name'            => 'My space',
                'applications'    => [],
                'total'           => 0,
                'trackingEnabled' => false,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/dashboard.twig',
                $this->callback(fn(array $params): bool => $params['freeText'] === 'smith'
                    && $params['isSearch'] === true
                    && $params['lpas'] === [])
            )
            ->willReturn('<html>search results</html>');

        $response = $this->handler->handle($this->createRequest(search: 'smith', identity: $identity));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testCalculatesPaginationCorrectly(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('lastLogin')->willReturn(new DateTime('2026-06-01 10:00:00'));

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getSharedSpaceLpaSummaries')
            ->with(null, 2, 50)
            ->willReturn([
                'name'            => 'My space',
                'applications'    => [
                    ['id' => 2001],
                ],
                'total'           => 120,
                'trackingEnabled' => false,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/dashboard.twig',
                $this->callback(function (array $params): bool {
                    $pagination = $params['paginationControlData'];

                    return $pagination['page'] === 2
                        && $pagination['pageCount'] === 3
                        && array_values($pagination['pagesInRange']) === [1, 2, 3]
                        && $pagination['firstItemNumber'] === 51
                        && $pagination['lastItemNumber'] === 100
                        && $pagination['totalItemCount'] === 120;
                })
            )
            ->willReturn('<html>page 2</html>');

        $response = $this->handler->handle($this->createRequest(page: 2, identity: $identity));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
