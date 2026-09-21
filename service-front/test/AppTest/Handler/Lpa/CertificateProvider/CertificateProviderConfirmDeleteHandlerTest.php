<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa\CertificateProvider;

use App\Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler;
use App\Middleware\RequestAttribute;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CertificateProviderConfirmDeleteHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private TemplateRendererInterface&MockObject $renderer;
    private UrlHelper&MockObject $urlHelper;
    private CertificateProviderConfirmDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new CertificateProviderConfirmDeleteHandler(
            $this->lpaApplicationService,
            $this->renderer,
            $this->urlHelper,
        );
    }

    private function createLpa(bool $withCorrespondent = false): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->version = 5;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];

        $cp = new CertificateProvider();
        $cp->name = new Name(['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Doe']);
        $cp->address = new Address(['address1' => '1 Road', 'postcode' => 'AB1 2CD']);
        $lpa->document->certificateProvider = $cp;

        if ($withCorrespondent) {
            $correspondent = new Correspondence();
            $correspondent->who = Correspondence::WHO_CERTIFICATE_PROVIDER;
            $lpa->document->correspondent = $correspondent;
        }

        return $lpa;
    }

    private function createRequest(string $method, Lpa $lpa, array $headers = [], array $postData = []): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/certificate-provider/confirm-delete');

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testRendersConfirmDeletePage(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/certificate-provider/confirm-delete.twig',
                $this->callback(function (array $params): bool {
                    $this->assertEquals('/lpa/91333263035/lpa/certificate-provider/confirm-delete', $params['actionUrl']);
                    $this->assertEquals(new Name(['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Doe']), $params['certificateProviderName']);
                    $this->assertEquals(new Address(['address1' => '1 Road', 'postcode' => 'AB1 2CD']), $params['certificateProviderAddress']);
                    $this->assertEquals('/lpa/91333263035/lpa/certificate-provider', $params['cancelUrl']);
                    $this->assertFalse($params['isPopup']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', $this->createLpa()));
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
            $this->createRequest('GET', $this->createLpa(), ['X-Requested-With' => 'XMLHttpRequest'])
        );
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }


    public function testDeletesCertificateProviderAndRedirects(): void
    {
        $lpa = $this->createLpa();

        $this->lpaApplicationService->expects($this->once())
            ->method('deleteCertificateProvider')
            ->with($lpa, 5)
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, postData: ['version' => '5']));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteThrowsOnApiFailure(): void
    {
        $this->lpaApplicationService->method('deleteCertificateProvider')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->handler->handle($this->createRequest('POST', $this->createLpa(), postData: ['version' => '5']));
    }

    public function testDeleteAlsoDeletesCorrespondentWhenCpIsCorrespondent(): void
    {
        $lpa = $this->createLpa(true);

        $this->lpaApplicationService->expects($this->once())
            ->method('deleteCorrespondent')
            ->with($lpa, 5)
            ->willReturn(true);
        $this->lpaApplicationService->expects($this->once())
            ->method('deleteCertificateProvider')
            ->with($lpa, 6)
            ->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', $lpa, postData: ['version' => '5']));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteDoesNotDeleteCorrespondentWhenDifferentWho(): void
    {
        $lpa = $this->createLpa();
        $correspondent = new Correspondence();
        $correspondent->who = Correspondence::WHO_DONOR;
        $lpa->document->correspondent = $correspondent;

        $this->lpaApplicationService->expects($this->never())
            ->method('deleteCorrespondent');
        $this->lpaApplicationService->expects($this->once())
            ->method('deleteCertificateProvider')
            ->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', $lpa, postData: ['version' => '5']));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
