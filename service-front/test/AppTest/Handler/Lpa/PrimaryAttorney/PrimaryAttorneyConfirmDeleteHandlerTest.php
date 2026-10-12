<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa\PrimaryAttorney;

use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use App\Middleware\RequestAttribute;
use App\Middleware\StubMiddleware;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Applicant as ApplicantService;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PrimaryAttorneyConfirmDeleteHandlerTest extends TestCase
{
    private MockObject&LpaApplicationService $lpaApplicationService;
    private MockObject&ApplicantService $applicantService;
    private MockObject&ReplacementAttorneyCleanup $replacementAttorneyCleanup;
    private MockObject&TemplateRendererInterface $renderer;
    private MockObject&UrlHelper $urlHelper;
    private PrimaryAttorneyConfirmDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->applicantService = $this->createMock(ApplicantService::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new PrimaryAttorneyConfirmDeleteHandler(
            $this->lpaApplicationService,
            $this->applicantService,
            $this->replacementAttorneyCleanup,
            $this->renderer,
            $this->urlHelper,
        );
    }

    private function createLpaWithAttorney(int $attorneyCount = 1): Lpa
    {
        $attorney = new Human();
        $attorney->id = 208925;
        $attorney->name = new Name(['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney']);
        $attorney->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);

        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->version = 4;
        $lpa->document = new Document();

        for ($i = 0; $i < $attorneyCount; $i++) {
            $a = clone $attorney;
            $a->id += $i;
            $lpa->document->primaryAttorneys[$i] = $a;
        }

        return $lpa;
    }

    private function createLpaWithTrustCorporation(): Lpa
    {
        $trust = new TrustCorporation();
        $trust->name = 'Test Trust Corp';
        $trust->number = '12345678';
        $trust->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);

        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->version = 4;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [0 => $trust];

        return $lpa;
    }

    private function createRequest(
        string $method,
        Lpa $lpa,
        int|string|null $idx = 0,
        array $postData = [],
    ): ServerRequest {
        $flowChecker = $this->createMock(FormFlowChecker::class);

        $route = new Route('/lpa/:lpa-id/primary-attorney/confirm-delete/:idx', new StubMiddleware(), null, 'lpa/primary-attorney/confirm-delete');
        $routeResult = RouteResult::fromRoute($route, ['lpa-id' => $lpa->id, 'idx' => $idx]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/primary-attorney/confirm-delete')
            ->withAttribute(RouteResult::class, $routeResult);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testRendersConfirmDeletePageForHumanAttorney(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/primary-attorney/confirm-delete.twig',
                $this->callback(function (array $params): bool {
                    $this->assertEquals('/lpa/91333263035/lpa/primary-attorney/confirm-delete', $params['actionUrl']);
                    $this->assertEquals(new Name(['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney']), $params['attorneyName']);
                    $this->assertEquals('/lpa/91333263035/lpa/primary-attorney', $params['cancelUrl']);
                    $this->assertFalse($params['isTrust']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', $this->createLpaWithAttorney()));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRendersConfirmDeletePageForTrust(): void
    {

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertTrue($params['isTrust']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', $this->createLpaWithTrustCorporation()));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidIdxReturns404(): void
    {
        $response = $this->handler->handle($this->createRequest('GET', $this->createLpaWithAttorney(), 999));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPostSuccessfullyRedirects(): void
    {
        $lpa = $this->createLpaWithAttorney();

        $this->applicantService
            ->expects($this->once())
            ->method('removeAttorney')
            ->with($lpa, 208925, 5)
            ->willReturn(6);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('deletePrimaryAttorney')
            ->with($lpa, 208925, 6)
            ->willReturn(true);

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp')
            ->with($lpa, 7);

        $response = $this->handler->handle($this->createRequest('POST', $lpa, postData: ['version' => '5']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(['/lpa/91333263035/lpa/primary-attorney'], $response->getHeader('Location'));
    }

    public function testDeleteThrowsExceptionWhenApiFails(): void
    {
        $this->lpaApplicationService
            ->method('deletePrimaryAttorney')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete a primary attorney');

        $this->handler->handle($this->createRequest('POST', $this->createLpaWithAttorney(), postData: ['version' => '5']));
    }


    public function testDeleteResetsHowDecisionsWhenOnlyTwoAttorneys(): void
    {
        $lpa = $this->createLpaWithAttorney(2);
        $decisions = new PrimaryAttorneyDecisions();
        $decisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY;
        $decisions->howDetails = 'some details';
        $lpa->document->primaryAttorneyDecisions = $decisions;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions')
            ->with($lpa, $decisions, 5);

        $this->applicantService
            ->expects($this->once())
            ->method('removeAttorney')
            ->with($lpa, 208925, 6)
            ->willReturn(7);

        $this->lpaApplicationService
            ->method('deletePrimaryAttorney')
            ->with($lpa, 208925, 7)
            ->willReturn(true);

        $this->handler->handle($this->createRequest('POST', $lpa, 0, postData: ['version' => '5']));
    }

    public function testDeleteDoesNotResetHowDecisionsWhenMoreThanTwoAttorneys(): void
    {
        $lpa = $this->createLpaWithAttorney(3);
        $decisions = new PrimaryAttorneyDecisions();
        $decisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;
        $lpa->document->primaryAttorneyDecisions = $decisions;

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPrimaryAttorneyDecisions');

        $this->lpaApplicationService
            ->method('deletePrimaryAttorney')
            ->willReturn(true);

        $this->handler->handle($this->createRequest('POST', $lpa, 0, postData: ['version' => '5']));
    }
}
