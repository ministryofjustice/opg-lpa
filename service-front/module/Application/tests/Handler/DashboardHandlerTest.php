<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\DashboardHandler;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Router\RouteMatch;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DashboardHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Identity&MockObject $identity;
    private DashboardHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->identity = $this->createMock(Identity::class);

        $this->identity->method('lastLogin')->willReturn('2024-01-01 12:00:00');

        $this->handler = new DashboardHandler(
            $this->renderer,
            $this->lpaApplicationService,
        );
    }

    private function createRequest(array $routeParams = [], array $queryParams = []): ServerRequest
    {
        $routeMatch = new RouteMatch($routeParams);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteMatch::class, $routeMatch)
            ->withAttribute(RequestAttribute::IDENTITY, $this->identity)
            ->withQueryParams($queryParams);
    }

    public function testRedirectsToCreateWhenNoLpasAndNoSearch(): void
    {
        $request = $this->createRequest();

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with(null, 1, 50)
            ->willReturn([
                'applications' => [],
                'total' => 0,
                'trackingEnabled' => false,
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard/create', $response->getHeaderLine('Location'));
    }

    public function testRendersIndexWithLpas(): void
    {
        $request = $this->createRequest();

        $lpas = [['id' => 1], ['id' => 2]];

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with(null, 1, 50)
            ->willReturn([
                'applications' => $lpas,
                'total' => 2,
                'trackingEnabled' => true,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/index.twig',
                $this->callback(function (array $params) use ($lpas) {
                    return $params['lpas'] === $lpas
                        && $params['lpaTotalCount'] === 2
                        && $params['trackingEnabled'] === true
                        && $params['isSearch'] === false
                        && $params['freeText'] === null
                        && $params['user']['lastLogin'] === '2024-01-01 12:00:00';
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersEmptyResultsForSearchWithNoResults(): void
    {
        $request = $this->createRequest([], ['search' => 'nonexistent']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with('nonexistent', 1, 50)
            ->willReturn([
                'applications' => [],
                'total' => 0,
                'trackingEnabled' => false,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/index.twig',
                $this->callback(function (array $params) {
                    return $params['lpas'] === []
                        && $params['isSearch'] === true
                        && $params['freeText'] === 'nonexistent';
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testUsesPageFromRouteMatch(): void
    {
        $request = $this->createRequest(['page' => '3']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with(null, 3, 50)
            ->willReturn([
                'applications' => [['id' => 1]],
                'total' => 150,
                'trackingEnabled' => false,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/index.twig',
                $this->callback(function (array $params) {
                    return $params['paginationControlData']['page'] === 3;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testDefaultsToPageOneWhenNotProvided(): void
    {
        $request = $this->createRequest();

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with(null, 1, 50)
            ->willReturn([
                'applications' => [['id' => 1]],
                'total' => 1,
                'trackingEnabled' => false,
            ]);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPageClampedToPageCountWhenExceedingTotal(): void
    {
        // Old test: testIndexAction - page 10 requested but only 1 item (1 page), should clamp to page 1
        $request = $this->createRequest(['page' => '10']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with(null, 10, 50)
            ->willReturn([
                'applications' => [['id' => 1]],
                'total' => 1,
                'trackingEnabled' => true,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/index.twig',
                $this->callback(function (array $params) {
                    $pagination = $params['paginationControlData'];
                    return $pagination['page'] === 1
                        && $pagination['pageCount'] === 1
                        && $pagination['pagesInRange'] === [1]
                        && $pagination['firstItemNumber'] === 1
                        && $pagination['lastItemNumber'] === 1
                        && $pagination['totalItemCount'] === 1;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPaginationWithMultiplePages(): void
    {
        // Old test: testIndexActionMultiplePages - 250 items, page 2, 5 pages total
        $request = $this->createRequest(['page' => '2']);

        $lpas = array_fill(0, 250, ['id' => 1]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with(null, 2, 50)
            ->willReturn([
                'applications' => $lpas,
                'total' => 250,
                'trackingEnabled' => true,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/index.twig',
                $this->callback(function (array $params) {
                    $pagination = $params['paginationControlData'];
                    // asort preserves keys, so compare values only
                    return $pagination['page'] === 2
                        && $pagination['pageCount'] === 5
                        && array_values($pagination['pagesInRange']) === [1, 2, 3, 4, 5]
                        && $pagination['firstItemNumber'] === 51
                        && $pagination['lastItemNumber'] === 100
                        && $pagination['totalItemCount'] === 250;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPaginationOnLastPage(): void
    {
        // Old test: testIndexActionLastPage - 150 items, page 3 (last page), 3 pages total
        $request = $this->createRequest(['page' => '3']);

        $lpas = array_fill(0, 150, ['id' => 1]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with(null, 3, 50)
            ->willReturn([
                'applications' => $lpas,
                'total' => 150,
                'trackingEnabled' => true,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/index.twig',
                $this->callback(function (array $params) {
                    $pagination = $params['paginationControlData'];
                    // asort preserves keys, so compare values only
                    return $pagination['page'] === 3
                        && $pagination['pageCount'] === 3
                        && array_values($pagination['pagesInRange']) === [1, 2, 3]
                        && $pagination['firstItemNumber'] === 101
                        && $pagination['lastItemNumber'] === 150
                        && $pagination['totalItemCount'] === 150;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSearchWithResultsRendersIndex(): void
    {
        $request = $this->createRequest([], ['search' => 'test']);

        $lpas = [['id' => 5]];

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->with('test', 1, 50)
            ->willReturn([
                'applications' => $lpas,
                'total' => 1,
                'trackingEnabled' => false,
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/index.twig',
                $this->callback(function (array $params) {
                    return $params['isSearch'] === true
                        && $params['freeText'] === 'test';
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
