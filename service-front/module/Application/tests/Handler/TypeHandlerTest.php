<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Form\Lpa\TypeForm;
use Application\Handler\TypeHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TypeHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private TypeForm&MockObject $form;
    private TypeHandler $handler;
    private Lpa $lpa;
    private FormFlowChecker&MockObject $flowChecker;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(TypeForm::class);
        $this->flowChecker = $this->createMock(FormFlowChecker::class);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\Lpa\TypeForm')
            ->willReturn($this->form);

        $this->handler = new TypeHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );

        $this->lpa = FixturesData::getHwLpa();
    }

    private function createRequest(string $method = 'GET', array $body = []): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $this->lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $this->flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/form-type')
            ->withAttribute('secondsUntilSessionExpires', 3600);

        if ($method === 'POST') {
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    public function testGetRequestRendersFormWithDocumentDataBound(): void
    {
        $this->flowChecker->method('nextRoute')->with('lpa/form-type')->willReturn('lpa/donor');
        $this->flowChecker->method('getRouteOptions')->willReturn([]);
        $this->form->expects($this->once())->method('bind')->with($this->lpa->document->flatten());

        $lpaId = $this->lpa->id;
        $this->urlHelper
            ->method('generate')
            ->willReturnCallback(function (string $route) use ($lpaId): string {
                return $route === 'lpa/donor'
                    ? '/lpa/' . $lpaId . '/donor'
                    : '/user/dashboard/create?lpa-id=' . $lpaId;
            });

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/type/index.twig',
                $this->callback(
                    fn($p) => $p['form'] === $this->form && isset($p['nextUrl']) && isset($p['cloneUrl'])
                )
            )
            ->willReturn('<html lang="en">type form</html>');

        $response = $this->handler->handle($this->createRequest('GET'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetRequestWithDonorDisallowsChange(): void
    {
        $lpaWithDonor = FixturesData::getHwLpa();
        $this->assertInstanceOf(Donor::class, $lpaWithDonor->document->donor);

        $this->flowChecker->method('nextRoute')->willReturn('lpa/donor');
        $this->flowChecker->method('getRouteOptions')->willReturn([]);
        $this->urlHelper->method('generate')->willReturn('/lpa/' . $lpaWithDonor->id . '/donor');
        $this->form->method('bind')->with($lpaWithDonor->document->flatten());

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/type/index.twig',
                $this->callback(fn($p) => $p['isChangeAllowed'] === false)
            )
            ->willReturn('<html lang="en">form</html>');

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpaWithDonor)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $this->flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/form-type')
            ->withAttribute('secondsUntilSessionExpires', 3600);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetRequestWithNoDocumentAllowsChange(): void
    {
        $this->lpa->document = new Document();

        $this->flowChecker->method('nextRoute')->willReturn('lpa/donor');
        $this->flowChecker->method('getRouteOptions')->willReturn([]);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/donor');
        $this->form->method('bind')->with($this->lpa->document->flatten());

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/type/index.twig',
                $this->callback(fn($p) => $p['isChangeAllowed'] === true)
            )
            ->willReturn('<html lang="en">form</html>');

        $response = $this->handler->handle($this->createRequest('GET'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidFormRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);
        $this->flowChecker->method('nextRoute')->willReturn('lpa/donor');
        $this->flowChecker->method('getRouteOptions')->willReturn([]);
        $this->urlHelper->method('generate')->willReturn('/lpa/' . $this->lpa->id . '/donor');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/authenticated/lpa/type/index.twig', $this->anything())
            ->willReturn('<html lang="en">form with errors</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['type' => '']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidFormWithSameTypeRedirectsWithoutCallingSetType(): void
    {
        $existingType = $this->lpa->document->type;

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['type' => $existingType]);
        $this->lpaApplicationService->expects($this->never())->method('setType');
        $this->flowChecker->method('nextRoute')->with('lpa/form-type')->willReturn('lpa/donor');
        $this->flowChecker->method('getRouteOptions')->willReturn([]);

        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('lpa/donor', ['lpa-id' => $this->lpa->id], [])
            ->willReturn('/lpa/' . $this->lpa->id . '/donor');

        $response = $this->handler->handle($this->createRequest('POST', ['type' => $existingType]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/donor', $response->getHeaderLine('Location'));
    }

    public function testPostValidFormWithDifferentTypeCallsSetTypeAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['type' => Document::LPA_TYPE_PF]);
        $this->lpa->document->type = Document::LPA_TYPE_HW;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setType')
            ->with($this->lpa, Document::LPA_TYPE_PF)
            ->willReturn(true);

        $this->flowChecker->method('nextRoute')->willReturn('lpa/donor');
        $this->flowChecker->method('getRouteOptions')->willReturn([]);

        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/lpa/' . $this->lpa->id . '/donor');

        $response = $this->handler->handle($this->createRequest('POST', ['type' => Document::LPA_TYPE_PF]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testPostValidFormSetTypeFailureThrowsException(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['type' => Document::LPA_TYPE_PF]);
        $this->lpa->document->type = Document::LPA_TYPE_HW;
        $this->lpaApplicationService->method('setType')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set LPA type for id: ' . $this->lpa->id);

        $this->handler->handle($this->createRequest('POST', ['type' => Document::LPA_TYPE_PF]));
    }
}
