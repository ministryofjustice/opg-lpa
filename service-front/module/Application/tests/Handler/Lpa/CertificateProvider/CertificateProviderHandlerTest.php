<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\CertificateProvider;

use Application\Handler\Lpa\CertificateProvider\CertificateProviderHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CertificateProviderHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private Metadata&MockObject $metadata;
    private CertificateProviderHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->metadata = $this->createMock(Metadata::class);

        $this->handler = new CertificateProviderHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->metadata,
        );
    }

    private function createLpa(bool $withCp = false): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];

        if ($withCp) {
            $lpa->document->certificateProvider = new CertificateProvider();
        }

        return $lpa;
    }

    private function createRequest(string $method = 'GET', ?Lpa $lpa = null, array $postData = []): ServerRequest
    {
        $lpa = $lpa ?? $this->createLpa();
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/people-to-notify');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/certificate-provider');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersIndexPage(): void
    {
        $form = $this->createMock(FormInterface::class);
        $this->formElementManager->method('get')->willReturn($form);
        $form->method('setAttribute')->willReturnSelf();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/certificate-provider/index.twig',
                $this->callback(function (array $params): bool {
                    $this->assertArrayHasKey('form', $params);
                    $this->assertArrayHasKey('nextRoute', $params);
                    $this->assertEquals('lpa/people-to-notify', $params['nextRoute']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());
        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostWithValidFormSkipsCertificateProvider(): void
    {
        $form = $this->createMock(FormInterface::class);
        $this->formElementManager->method('get')->willReturn($form);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(true);

        $this->metadata
            ->expects($this->once())
            ->method('setCertificateProviderSkipped');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/people-to-notify');

        $response = $this->handler->handle($this->createRequest('POST', null, ['submit' => 'skip']));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostWithInvalidFormRendersPage(): void
    {
        $form = $this->createMock(FormInterface::class);
        $this->formElementManager->method('get')->willReturn($form);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('POST', null, ['submit' => 'skip']));
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
