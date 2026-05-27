<?php

declare(strict_types=1);

namespace AppTest\Model\Service\Session;

use App\Model\Service\Session\PersistentSessionDetails;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PersistentSessionDetailsTest extends TestCase
{
    private SessionInterface&MockObject $session;
    private PersistentSessionDetails $details;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->details = new PersistentSessionDetails();
    }

    private function makeRouteResult(string $routeName): RouteResult
    {
        $stub = new class implements MiddlewareInterface {
            public function process(
                \Psr\Http\Message\ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): \Psr\Http\Message\ResponseInterface {
                return $handler->handle($request);
            }
        };
        $route = new Route('/' . $routeName, $stub, null, $routeName);
        return RouteResult::fromRoute($route, []);
    }

    public function testRefreshSetsCurrentRouteAndRoutestoreWhenNoExistingData(): void
    {
        $routeResult = $this->makeRouteResult('lpa/applicant');

        $this->session->method('get')->with('SessionDetails', [])->willReturn([]);
        $this->session->expects($this->once())
            ->method('set')
            ->with('SessionDetails', $this->callback(function ($data) {
                return $data['currentRoute'] === 'lpa/applicant'
                    && $data['routeStore'] === 'lpa/applicant';
            }));

        $this->details->refresh($routeResult, $this->session);
    }

    public function testRefreshUpdatesPreviousRouteWhenRoutestoreDiffers(): void
    {
        $routeResult = $this->makeRouteResult('lpa/applicant');

        $existingData = [
            'routeStore' => 'lpa/type',
            'previousRoute' => 'lpa/donor',
        ];

        $this->session->method('get')->with('SessionDetails', [])->willReturn($existingData);
        $this->session->expects($this->once())
            ->method('set')
            ->with('SessionDetails', $this->callback(function ($data) {
                return $data['currentRoute'] === 'lpa/applicant'
                    && $data['routeStore'] === 'lpa/applicant'
                    && $data['previousRoute'] === 'lpa/type'; // routeStore becomes previousRoute
            }));

        $this->details->refresh($routeResult, $this->session);
    }

    public function testRefreshDoesNotUpdatePreviousRouteWhenRoutestoreMatchesPrevious(): void
    {
        $routeResult = $this->makeRouteResult('lpa/applicant');

        $existingData = [
            'routeStore' => 'lpa/donor',
            'previousRoute' => 'lpa/donor',
        ];

        $this->session->method('get')->with('SessionDetails', [])->willReturn($existingData);
        $this->session->expects($this->once())
            ->method('set')
            ->with('SessionDetails', $this->callback(function ($data) {
                // previousRoute should remain unchanged since routeStore === previousRoute
                return !array_key_exists('previousRoute', $data) || $data['previousRoute'] === 'lpa/donor';
            }));

        $this->details->refresh($routeResult, $this->session);
    }

    public function testRefreshWithNullRouteResultSetsEmptyCurrentRoute(): void
    {
        $this->session->method('get')->with('SessionDetails', [])->willReturn([]);
        $this->session->expects($this->once())
            ->method('set')
            ->with('SessionDetails', $this->callback(function ($data) {
                return $data['currentRoute'] === '' && $data['routeStore'] === '';
            }));

        $this->details->refresh(null, $this->session);
    }

    public function testGetCurrentRouteReturnsCurrentRoute(): void
    {
        $routeResult = $this->makeRouteResult('lpa/donor');

        $this->session->method('get')
            ->with('SessionDetails', [])
            ->willReturnOnConsecutiveCalls(
                [], // first call in refresh()
                ['currentRoute' => 'lpa/donor', 'routeStore' => 'lpa/donor'], // second call in getCurrentRoute()
            );
        $this->session->method('set');

        $this->details->refresh($routeResult, $this->session);
        $this->assertEquals('lpa/donor', $this->details->getCurrentRoute());
    }

    public function testGetPreviousRouteReturnsHomeWhenNotSet(): void
    {
        $this->session->method('get')
            ->with('SessionDetails', [])
            ->willReturnOnConsecutiveCalls(
                [], // first call in refresh()
                [], // second call in getPreviousRoute()
            );
        $this->session->method('set');

        $this->details->refresh(null, $this->session);

        $this->assertEquals('home', $this->details->getPreviousRoute());
    }
}
