<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\CertificateProvider;

use Application\Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CertificateProviderConfirmDeleteHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private MvcUrlHelper&MockObject $urlHelper;
    private CertificateProviderConfirmDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new CertificateProviderConfirmDeleteHandler(
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
        $lpa->document->peopleToNotify = [];

        $cp = new CertificateProvider();
        $cp->name = new Name(['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Doe']);
        $cp->address = new Address(['address1' => '1 Road', 'postcode' => 'AB1 2CD']);
        $lpa->document->certificateProvider = $cp;

        return $lpa;
    }

    private function createRequest(?Lpa $lpa = null, array $headers = []): ServerRequest
    {
        $lpa = $lpa ?? $this->createLpa();

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/certificate-provider/confirm-delete');

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    public function testRendersConfirmDeletePage(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/certificate-provider/confirm-delete.twig',
                $this->callback(function (array $params): bool {
                    $this->assertArrayHasKey('deleteRoute', $params);
                    $this->assertArrayHasKey('certificateProviderName', $params);
                    $this->assertArrayHasKey('certificateProviderAddress', $params);
                    $this->assertArrayHasKey('isPopup', $params);
                    $this->assertArrayHasKey('cancelUrl', $params);
                    $this->assertFalse($params['isPopup']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersAsPopupWhenXhr(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertTrue($params['isPopup']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest(null, ['X-Requested-With' => 'XMLHttpRequest'])
        );
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testHandlesNullCertificateProvider(): void
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];
        $lpa->document->certificateProvider = null;

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertNull($params['certificateProviderName']);
                    $this->assertNull($params['certificateProviderAddress']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest($lpa));
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
