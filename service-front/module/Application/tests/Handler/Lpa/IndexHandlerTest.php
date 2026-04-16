<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\IndexHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexHandlerTest extends TestCase
{
    private Metadata&MockObject $metadata;
    private MvcUrlHelper&MockObject $urlHelper;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private IndexHandler $handler;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(Metadata::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) => '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new IndexHandler(
            $this->metadata,
            $this->urlHelper,
            $this->sessionManagerSupport,
        );
    }

    private function createRequest(?Lpa $lpa = null, string $destinationRoute = 'lpa/donor'): ServerRequest
    {
        $lpa = $lpa ?? FixturesData::getPfLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn($destinationRoute);
        $flowChecker->method('getRouteOptions')->willReturn([]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker);
    }

    public function testHandleIncrementsAnalyticsReturnCountAndRedirects(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->seed = null;
        $lpa->metadata = [];

        $this->metadata->expects($this->once())
            ->method('setAnalyticsReturnCount')
            ->with($lpa, 1);

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('lpa/donor', $response->getHeaderLine('Location'));
    }

    public function testHandleIncrementsExistingAnalyticsReturnCount(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->seed = null;
        $lpa->metadata = ['analyticsReturnCount' => 5];

        $this->metadata->expects($this->once())
            ->method('setAnalyticsReturnCount')
            ->with($lpa, 6);

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testHandleResetsSessionCloneDataWhenSeedIdPresent(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->seed = 12345;
        $lpa->metadata = [];

        $storage = new ArrayStorage();
        $storage['cloneData']['12345'] = ['some' => 'data'];

        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->method('getStorage')->willReturn($storage);

        $this->sessionManagerSupport->method('getSessionManager')->willReturn($sessionManager);

        $this->handler->handle($this->createRequest($lpa));

        $this->assertArrayNotHasKey('12345', (array) $storage['cloneData']);
    }

    public function testHandleDoesNotResetSessionDataWhenNoSeedId(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->seed = null;
        $lpa->metadata = [];

        $this->sessionManagerSupport->expects($this->never())->method('getSessionManager');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testHandleRedirectsToDestinationFromFlowChecker(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->seed = null;
        $lpa->metadata = [];

        $response = $this->handler->handle($this->createRequest($lpa, 'lpa/certificate-provider'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('certificate-provider', $response->getHeaderLine('Location'));
    }
}
