<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PrimaryAttorney;

use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PrimaryAttorneyDeleteHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ApplicantService&MockObject $applicantService;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    private PrimaryAttorneyDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->applicantService = $this->createMock(ApplicantService::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);

        $this->handler = new PrimaryAttorneyDeleteHandler(
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->applicantService,
            $this->replacementAttorneyCleanup,
        );
    }

    private function createHumanAttorney(int $id = 1): Human
    {
        $attorney = new Human();
        $attorney->id = $id;
        $attorney->name = new Name(['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney']);
        $attorney->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);

        return $attorney;
    }

    private function createLpa(int $attorneyCount = 1): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->type = Document::LPA_TYPE_PF;
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];
        $lpa->document->correspondent = null;
        $lpa->document->primaryAttorneyDecisions = null;

        for ($i = 0; $i < $attorneyCount; $i++) {
            $lpa->document->primaryAttorneys[$i] = $this->createHumanAttorney($i + 1);
        }

        return $lpa;
    }

    private function createRequest(
        ?Lpa $lpa = null,
        int|string|null $idx = 0,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $route = new Route('/lpa/:lpa-id/primary-attorney/delete/:idx', new \Application\Middleware\StubMiddleware(), null, 'lpa/primary-attorney/delete');
        $routeResult = RouteResult::fromRoute($route, ['lpa-id' => $lpa->id, 'idx' => $idx]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE, 'lpa/primary-attorney/delete')
            ->withAttribute(RouteResult::class, $routeResult);
    }

    public function testDeleteAttorneySuccessfullyRedirects(): void
    {
        $this->lpaApplicationService
            ->expects($this->once())
            ->method('deletePrimaryAttorney')
            ->willReturn(true);

        $this->applicantService
            ->expects($this->once())
            ->method('removeAttorney');

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteInvalidIdxReturns404(): void
    {
        $response = $this->handler->handle($this->createRequest(null, 999));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDeleteThrowsExceptionWhenApiFails(): void
    {
        $this->lpaApplicationService
            ->method('deletePrimaryAttorney')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete a primary attorney');

        $this->handler->handle($this->createRequest());
    }

    public function testDeleteResetsHowDecisionsWhenOnlyTwoAttorneys(): void
    {
        $lpa = $this->createLpa(2);
        $decisions = new PrimaryAttorneyDecisions();
        $decisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY;
        $decisions->howDetails = 'some details';
        $lpa->document->primaryAttorneyDecisions = $decisions;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions');

        $this->lpaApplicationService
            ->method('deletePrimaryAttorney')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->handler->handle($this->createRequest($lpa, 0));
    }

    public function testDeleteDoesNotResetHowDecisionsWhenMoreThanTwoAttorneys(): void
    {
        $lpa = $this->createLpa(3);
        $decisions = new PrimaryAttorneyDecisions();
        $decisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;
        $lpa->document->primaryAttorneyDecisions = $decisions;

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPrimaryAttorneyDecisions');

        $this->lpaApplicationService
            ->method('deletePrimaryAttorney')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->handler->handle($this->createRequest($lpa, 0));
    }
}
