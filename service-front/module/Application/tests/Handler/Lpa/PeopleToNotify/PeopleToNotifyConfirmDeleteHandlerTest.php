<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PeopleToNotify;

use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PeopleToNotifyConfirmDeleteHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private MvcUrlHelper&MockObject $urlHelper;
    private PeopleToNotifyConfirmDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new PeopleToNotifyConfirmDeleteHandler(
            $this->renderer,
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
        $np->id = 1;
        $np->name = new Name(['title' => 'Mr', 'first' => 'John', 'last' => 'Notify']);
        $np->address = new Address(['address1' => '1 Road', 'postcode' => 'AB1 2CD']);
        $lpa->document->peopleToNotify = [$np];

        return $lpa;
    }

    private function createRequest(Lpa $lpa, ?string $idx = '0', array $headers = []): ServerRequest
    {
        $routeParams = ['lpa-id' => $lpa->id];
        if ($idx !== null) {
            $routeParams['idx'] = $idx;
        }
        $route = new Route('/lpa/:lpa-id/people-to-notify/confirm-delete/:idx', new \Application\Middleware\StubMiddleware(), null, 'lpa/people-to-notify/confirm-delete');
        $routeResult = RouteResult::fromRoute($route, $routeParams);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RouteResult::class, $routeResult);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    public function testRendersConfirmDeletePage(): void
    {
        $lpa = $this->createLpa();

        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())->method('render')->willReturn('<html>confirm</html>');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
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

    public function testPopupRequestSetsIsPopup(): void
    {
        $lpa = $this->createLpa();

        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    return isset($params['isPopup']) && $params['isPopup'] === true;
                })
            )
            ->willReturn('<html>confirm</html>');

        $response = $this->handler->handle(
            $this->createRequest($lpa, '0', ['X-Requested-With' => 'XMLHttpRequest'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
