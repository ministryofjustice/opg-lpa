<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\RepeatApplicationForm;
use Application\Handler\Lpa\RepeatApplicationHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RepeatApplicationHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private Metadata&MockObject $metadata;
    private RepeatApplicationForm&MockObject $form;
    private RepeatApplicationHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->form = $this->createMock(RepeatApplicationForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new RepeatApplicationHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->metadata,
        );
    }

    private function createLpa(?int $repeatCaseNumber = null, bool $withMetadata = true): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->payment = new Payment();
        $lpa->repeatCaseNumber = $repeatCaseNumber;
        $lpa->metadata = [];

        if ($withMetadata) {
            $lpa->metadata[Lpa::REPEAT_APPLICATION_CONFIRMED] = true;
        }

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        array $headers = [],
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/fee-reduction');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/repeat-application');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }


    public function testWithNoMetadataRendersFormWithoutBind(): void
    {
        $lpa = $this->createLpa(null, false);

        $this->form
            ->expects($this->never())
            ->method('bind');

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testWithMetadataAndNoRepeatCaseBindsIsNew(): void
    {
        $lpa = $this->createLpa(null, true);

        $this->form
            ->expects($this->once())
            ->method('bind')
            ->with([
                'isRepeatApplication' => 'is-new',
                'repeatCaseNumber'    => null,
            ]);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testWithMetadataAndRepeatCaseBindsIsRepeat(): void
    {
        $lpa = $this->createLpa(12345, true);

        $this->form
            ->expects($this->once())
            ->method('bind')
            ->with([
                'isRepeatApplication' => 'is-repeat',
                'repeatCaseNumber'    => 12345,
            ]);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersCorrectTemplate(): void
    {
        $lpa = $this->createLpa();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/repeat-application/index.twig',
                $this->callback(function (array $params): bool {
                    return isset($params['form'])
                        && isset($params['lpa']);
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    public function testInvalidRendersForm(): void
    {
        $lpa = $this->createLpa();

        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['isRepeatApplication' => 'is-new'], $lpa)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testNoRepeatSetsValidationGroupToIsRepeatApplicationOnly(): void
    {
        $lpa = $this->createLpa();
        $postData = ['isRepeatApplication' => 'is-new'];

        $this->form
            ->expects($this->once())
            ->method('setValidationGroup')
            ->with(['isRepeatApplication']);

        $this->form->method('isValid')->willReturn(false);
        $this->renderer->method('render')->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    public function testRepeatDoesNotSetValidationGroup(): void
    {
        $lpa = $this->createLpa();
        $postData = [
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber' => '12345',
        ];

        $this->form
            ->expects($this->never())
            ->method('setValidationGroup');

        $this->form->method('isValid')->willReturn(false);
        $this->renderer->method('render')->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    public function testRepeatSetsRepeatCaseNumberAndRedirects(): void
    {
        $lpa = $this->createLpa();
        $postData = [
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber' => '12345',
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setRepeatCaseNumber')
            ->with($this->anything(), '12345')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPayment')
            ->willReturn(true);

        $this->metadata
            ->expects($this->once())
            ->method('setRepeatApplicationConfirmed');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/fee-reduction');

        $response = $this->handler->handle($this->createRequest('POST', $postData, $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/fee-reduction', $response->getHeaderLine('Location'));
    }

    public function testRepeatWithSameCaseNumberSkipsApiCall(): void
    {
        $lpa = $this->createLpa(12345);
        $postData = [
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber' => 12345,
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setRepeatCaseNumber');

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPayment');

        $this->metadata
            ->expects($this->once())
            ->method('setRepeatApplicationConfirmed');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/fee-reduction');

        $response = $this->handler->handle($this->createRequest('POST', $postData, $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRepeatSetCaseNumberApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa();
        $postData = [
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber' => '12345',
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->method('setRepeatCaseNumber')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set repeat case number for id: 91333263035');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    public function testNoRepeatDeletesCaseNumberAndRedirects(): void
    {
        $lpa = $this->createLpa(12345);
        $postData = ['isRepeatApplication' => 'is-new'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('deleteRepeatCaseNumber')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPayment')
            ->willReturn(true);

        $this->metadata
            ->expects($this->once())
            ->method('setRepeatApplicationConfirmed');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/fee-reduction');

        $response = $this->handler->handle($this->createRequest('POST', $postData, $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testNoRepeatWithNoCaseNumberSkipsDeleteApi(): void
    {
        $lpa = $this->createLpa(null);
        $postData = ['isRepeatApplication' => 'is-new'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('deleteRepeatCaseNumber');

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPayment');

        $this->metadata
            ->expects($this->once())
            ->method('setRepeatApplicationConfirmed');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/fee-reduction');

        $response = $this->handler->handle($this->createRequest('POST', $postData, $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testNoRepeatDeleteApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa(12345);
        $postData = ['isRepeatApplication' => 'is-new'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->method('deleteRepeatCaseNumber')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set repeat case number for id: 91333263035');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    public function testRepeatRecalculatesPaymentWhenCaseNumberChanges(): void
    {
        $lpa = $this->createLpa(null);
        $postData = [
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber' => '99999',
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->method('setRepeatCaseNumber')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPayment')
            ->with(
                $this->anything(),
                $this->callback(function (Payment $payment): bool {
                    // Repeat application fee = half of 92 = 46
                    return $payment->amount == 46;
                })
            )
            ->willReturn(true);

        $this->metadata->method('setRepeatApplicationConfirmed');
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/fee-reduction');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    public function testSetPaymentApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa(null);
        $postData = [
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber' => '99999',
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->method('setRepeatCaseNumber')
            ->willReturn(true);

        $this->lpaApplicationService
            ->method('setPayment')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'API client failed to set payment details for id: 91333263035 in RepeatApplicationHandler'
        );

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    public function testNoRepeatRecalculatesPaymentToFullFee(): void
    {
        $lpa = $this->createLpa(12345);
        $postData = ['isRepeatApplication' => 'is-new'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->method('deleteRepeatCaseNumber')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPayment')
            ->with(
                $this->anything(),
                $this->callback(function (Payment $payment): bool {
                    // Full fee = 92
                    return $payment->amount == 92;
                })
            )
            ->willReturn(true);

        $this->metadata->method('setRepeatApplicationConfirmed');
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/fee-reduction');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    public function testValidXmlHttpRequestReturnsJsonResponse(): void
    {
        $lpa = $this->createLpa(null);
        $postData = ['isRepeatApplication' => 'is-new'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->metadata->method('setRepeatApplicationConfirmed');

        $response = $this->handler->handle(
            $this->createRequest('POST', $postData, $lpa, ['X-Requested-With' => 'XMLHttpRequest'])
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testWithNullParsedBodyHandlesGracefully(): void
    {
        $lpa = $this->createLpa();

        $this->form->method('isValid')->willReturn(false);
        $this->renderer->method('render')->willReturn('rendered-html');

        $flowChecker = $this->createMock(FormFlowChecker::class);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/repeat-application');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testDoesNotBindOnPost(): void
    {
        $lpa = $this->createLpa(12345, true);
        $postData = ['isRepeatApplication' => 'is-new'];

        $this->form
            ->expects($this->never())
            ->method('bind');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService->method('deleteRepeatCaseNumber')->willReturn(true);
        $this->lpaApplicationService->method('setPayment')->willReturn(true);
        $this->metadata->method('setRepeatApplicationConfirmed');
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/fee-reduction');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    public function testNoPaymentSkipsPaymentRecalculation(): void
    {
        $lpa = $this->createLpa(12345);
        $lpa->payment = null;

        $postData = ['isRepeatApplication' => 'is-new'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->method('deleteRepeatCaseNumber')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPayment');

        $this->metadata->method('setRepeatApplicationConfirmed');
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/fee-reduction');

        $response = $this->handler->handle($this->createRequest('POST', $postData, $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
