<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa\CertificateProvider;

use App\Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler;
use App\Middleware\RequestAttribute;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CertificateProviderDeleteHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private UrlHelper&MockObject $urlHelper;
    private CertificateProviderDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->handler = new CertificateProviderDeleteHandler(
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(bool $withCorrespondent = false): Lpa
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

        if ($withCorrespondent) {
            $correspondent = new Correspondence();
            $correspondent->who = Correspondence::WHO_CERTIFICATE_PROVIDER;
            $lpa->document->correspondent = $correspondent;
        }

        return $lpa;
    }

    private function createRequest(?Lpa $lpa = null): ServerRequest
    {
        $lpa = $lpa ?? $this->createLpa();

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa);
    }

    public function testDeletesCertificateProviderAndRedirects(): void
    {
        $this->lpaApplicationService->expects($this->once())
            ->method('deleteCertificateProvider')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle($this->createRequest());
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteThrowsOnApiFailure(): void
    {
        $this->lpaApplicationService->method('deleteCertificateProvider')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->handler->handle($this->createRequest());
    }

    public function testDeleteAlsoDeletesCorrespondentWhenCpIsCorrespondent(): void
    {
        $lpa = $this->createLpa(true);

        $this->lpaApplicationService->expects($this->once())
            ->method('deleteCorrespondent')
            ->willReturn(true);
        $this->lpaApplicationService->expects($this->once())
            ->method('deleteCertificateProvider')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle($this->createRequest($lpa));
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

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle($this->createRequest($lpa));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
