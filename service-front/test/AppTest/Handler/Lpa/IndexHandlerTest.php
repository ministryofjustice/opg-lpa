<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\Lpa\IndexHandler;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexHandlerTest extends TestCase
{
    private Metadata&MockObject $metadata;
    private UrlHelper&MockObject $urlHelper;
    private IndexHandler $handler;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(Metadata::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) => '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new IndexHandler(
            $this->metadata,
            $this->urlHelper,
        );
    }

    private function createRequest(
        ?Lpa $lpa = null,
        string $destinationRoute = 'lpa/donor',
        ?SessionInterface $session = null
    ): ServerRequest {
        $lpa = $lpa ?? FixturesData::getPfLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn($destinationRoute);
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker);

        if ($session !== null) {
            $request = $request->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);
        }

        return $request;
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

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('clone_data')
            ->willReturn(['12345' => ['some' => 'data'], '99999' => ['other' => 'data']]);

        $session->expects($this->once())
            ->method('set')
            ->with('clone_data', ['99999' => ['other' => 'data']]);

        $this->handler->handle($this->createRequest($lpa, 'lpa/donor', $session));
    }

    public function testHandleDoesNotResetSessionDataWhenNoSeedId(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->seed = null;
        $lpa->metadata = [];

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->never())->method('get');
        $session->expects($this->never())->method('set');

        $response = $this->handler->handle($this->createRequest($lpa, 'lpa/donor', $session));

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
