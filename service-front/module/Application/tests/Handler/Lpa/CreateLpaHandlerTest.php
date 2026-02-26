<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\CreateLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\StorageInterface;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateLpaHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private FlashMessenger&MockObject $flashMessenger;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private SessionManager&MockObject $sessionManager;
    private StorageInterface&MockObject $sessionStorage;
    private CreateLpaHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->sessionStorage = $this->createMock(StorageInterface::class);

        $this->sessionManagerSupport->method('getSessionManager')->willReturn($this->sessionManager);
        $this->sessionManager->method('getStorage')->willReturn($this->sessionStorage);

        $this->handler = new CreateLpaHandler(
            $this->lpaApplicationService,
            $this->flashMessenger,
            $this->sessionManagerSupport,
        );
    }

    private function createRequestWithParams(array $routeParams = []): ServerRequest
    {
        $routeMatch = new RouteMatch($routeParams);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteMatch::class, $routeMatch);
    }

    public function testRedirectsToLpaTypeWhenNoSeedId(): void
    {
        $request = $this->createRequestWithParams();

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('createApplication');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/type', $response->getHeaderLine('Location'));
    }

    public function testCreatesLpaAndRedirectsToFormType(): void
    {
        $request = $this->createRequestWithParams(['lpa-id' => '123']);

        $lpa = new Lpa();
        $lpa->id = 456;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('createApplication')
            ->willReturn($lpa);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setSeed')
            ->with($lpa, '123')
            ->willReturn(true);

        $this->flashMessenger
            ->expects($this->never())
            ->method('addErrorMessage');

        $this->flashMessenger
            ->expects($this->never())
            ->method('addWarningMessage');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/456/type', $response->getHeaderLine('Location'));
    }

    public function testShowsErrorWhenCreateApplicationFails(): void
    {
        $request = $this->createRequestWithParams(['lpa-id' => '123']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('createApplication')
            ->willReturn(false);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('Error creating a new LPA. Please try again.');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testShowsWarningWhenSetSeedFails(): void
    {
        $request = $this->createRequestWithParams(['lpa-id' => '123']);

        $lpa = new Lpa();
        $lpa->id = 456;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('createApplication')
            ->willReturn($lpa);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setSeed')
            ->with($lpa, '123')
            ->willReturn(false);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addWarningMessage')
            ->with('LPA created but could not set seed');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/456/type', $response->getHeaderLine('Location'));
    }

    public function testSessionCloneDataIsReset(): void
    {
        $request = $this->createRequestWithParams(['lpa-id' => '123']);

        $lpa = new Lpa();
        $lpa->id = 456;

        $this->lpaApplicationService->method('createApplication')->willReturn($lpa);
        $this->lpaApplicationService->method('setSeed')->willReturn(true);

        // The handler calls getSessionManager()->getStorage() to reset clone data
        $this->sessionManagerSupport
            ->expects($this->atLeastOnce())
            ->method('getSessionManager');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
