<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PeopleToNotify;

use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PeopleToNotifyDeleteHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private PeopleToNotifyDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new PeopleToNotifyDeleteHandler(
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];

        $np = new NotifiedPerson();
        $np->id = 42;
        $np->name = new Name(['title' => 'Mr', 'first' => 'John', 'last' => 'Notify']);
        $np->address = new Address(['address1' => '1 Road', 'postcode' => 'AB1 2CD']);
        $lpa->document->peopleToNotify = [$np];

        return $lpa;
    }

    private function createRequest(Lpa $lpa, ?string $idx = '0'): ServerRequest
    {
        $routeParams = ['lpa-id' => $lpa->id];
        if ($idx !== null) {
            $routeParams['idx'] = $idx;
        }
        $route = new Route('/lpa/:lpa-id/people-to-notify/delete/:idx', new \Application\Middleware\StubMiddleware(), null, 'lpa/people-to-notify/delete');
        $routeResult = RouteResult::fromRoute($route, $routeParams);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RouteResult::class, $routeResult);
    }

    public function testDeletesPersonAndRedirects(): void
    {
        $lpa = $this->createLpa();

        $this->lpaApplicationService->expects($this->once())
            ->method('deleteNotifiedPerson')
            ->with($lpa, 42)
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/people-to-notify');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteThrowsOnApiFailure(): void
    {
        $lpa = $this->createLpa();

        $this->lpaApplicationService->method('deleteNotifiedPerson')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->handler->handle($this->createRequest($lpa));
    }

    public function testReturns404ForInvalidIndex(): void
    {
        $lpa = $this->createLpa();

        $response = $this->handler->handle($this->createRequest($lpa, '99'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testReturns404ForNullIndex(): void
    {
        $lpa = $this->createLpa();

        $response = $this->handler->handle($this->createRequest($lpa, null));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }
}
