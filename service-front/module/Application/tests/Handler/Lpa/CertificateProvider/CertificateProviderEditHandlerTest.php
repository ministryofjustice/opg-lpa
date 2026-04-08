<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\CertificateProvider;

use Application\Form\Lpa\AbstractActorForm;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderEditHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CertificateProviderEditHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private CertificateProviderEditHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new CertificateProviderEditHandler(
            $this->renderer,
            $this->formElementManager,
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
        $lpa->document->peopleToNotify = [];

        $cp = new CertificateProvider();
        $cp->name = new Name(['title' => 'Mrs', 'first' => 'Existing', 'last' => 'CP']);
        $cp->address = new Address(['address1' => '1 Road', 'postcode' => 'AB1 2CD']);
        $lpa->document->certificateProvider = $cp;

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        ?Lpa $lpa = null,
        array $postData = [],
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/people-to-notify');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/certificate-provider/edit');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    private function createForm(): AbstractActorForm&MockObject
    {
        $form = $this->createMock(AbstractActorForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('setActorData')->willReturnSelf();
        return $form;
    }

    public function testGetBindsExistingCertificateProviderToForm(): void
    {
        $form = $this->createForm();
        $form->expects($this->once())->method('bind');

        $this->formElementManager->method('get')->willReturn($form);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/certificate-provider/form.twig',
                $this->callback(function (array $params): bool {
                    $this->assertArrayHasKey('form', $params);
                    $this->assertArrayHasKey('isPopup', $params);
                    $this->assertArrayHasKey('cancelUrl', $params);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormUpdatesCertificateProvider(): void
    {
        $form = $this->createForm();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mrs', 'first' => 'Updated', 'last' => 'CP'],
            'address' => ['address1' => '2 Road', 'postcode' => 'EF3 4GH'],
        ]);

        $this->formElementManager->method('get')->willReturn($form);
        $this->lpaApplicationService->expects($this->once())
            ->method('setCertificateProvider')
            ->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/people-to-notify');

        $response = $this->handler->handle(
            $this->createRequest('POST', null, ['name-first' => 'Updated'])
        );
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostWithValidFormPopupReturnsJson(): void
    {
        $form = $this->createForm();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mrs', 'first' => 'Updated', 'last' => 'CP'],
            'address' => ['address1' => '2 Road', 'postcode' => 'EF3 4GH'],
        ]);

        $this->formElementManager->method('get')->willReturn($form);
        $this->lpaApplicationService->method('setCertificateProvider')->willReturn(true);

        $request = $this->createRequest('POST', null, ['name-first' => 'Updated'])
            ->withHeader('X-Requested-With', 'XMLHttpRequest');

        $response = $this->handler->handle($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testPostWithInvalidFormRendersPage(): void
    {
        $form = $this->createForm();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(false);

        $this->formElementManager->method('get')->willReturn($form);
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', null, ['name-first' => ''])
        );
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithApiFailureThrowsException(): void
    {
        $form = $this->createForm();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mrs', 'first' => 'Updated', 'last' => 'CP'],
            'address' => ['address1' => '2 Road', 'postcode' => 'EF3 4GH'],
        ]);

        $this->formElementManager->method('get')->willReturn($form);
        $this->lpaApplicationService->method('setCertificateProvider')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->handler->handle(
            $this->createRequest('POST', null, ['name-first' => 'Updated'])
        );
    }
}
