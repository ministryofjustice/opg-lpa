<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\FeeReductionForm;
use Application\Handler\Lpa\FeeReductionHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\Element\Radio;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FeeReductionHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private FeeReductionForm&MockObject $form;
    private FeeReductionHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(FeeReductionForm::class);

        $reductionOptions = $this->createMock(Radio::class);
        $reductionOptions->method('getOptions')->willReturn([
            'value_options' => [
                'reducedFeeReceivesBenefits' => ['value' => 'reducedFeeReceivesBenefits'],
                'reducedFeeUniversalCredit'  => ['value' => 'reducedFeeUniversalCredit'],
                'reducedFeeLowIncome'        => ['value' => 'reducedFeeLowIncome'],
                'notApply'                   => ['value' => 'notApply'],
            ],
        ]);
        $reductionOptions->method('getValue')->willReturn('');

        $this->form->method('get')
            ->with('reductionOptions')
            ->willReturn($reductionOptions);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new FeeReductionHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(?Payment $payment = null): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->payment = $payment;

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/checkout');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/fee-reduction');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    // ==================== GET Tests ====================

    #[Test]
    public function getWithNoExistingPaymentRendersFormWithoutBind(): void
    {
        $lpa = $this->createLpa();

        $this->form
            ->expects($this->never())
            ->method('bind');

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function getWithExistingPaymentReducedFeeReceivesBenefitsBindsCorrectly(): void
    {
        $payment = new Payment();
        $payment->reducedFeeReceivesBenefits = true;
        $payment->reducedFeeAwardedDamages = true;

        $lpa = $this->createLpa($payment);

        $this->form
            ->expects($this->once())
            ->method('bind')
            ->with(['reductionOptions' => 'reducedFeeReceivesBenefits']);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function getWithExistingPaymentReducedFeeUniversalCreditBindsCorrectly(): void
    {
        $payment = new Payment();
        $payment->reducedFeeUniversalCredit = true;

        $lpa = $this->createLpa($payment);

        $this->form
            ->expects($this->once())
            ->method('bind')
            ->with(['reductionOptions' => 'reducedFeeUniversalCredit']);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function getWithExistingPaymentReducedFeeLowIncomeBindsCorrectly(): void
    {
        $payment = new Payment();
        $payment->reducedFeeLowIncome = true;

        $lpa = $this->createLpa($payment);

        $this->form
            ->expects($this->once())
            ->method('bind')
            ->with(['reductionOptions' => 'reducedFeeLowIncome']);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function getWithExistingPaymentNotApplyBindsCorrectly(): void
    {
        $payment = new Payment();
        $payment->reducedFeeReceivesBenefits = false;
        $payment->reducedFeeAwardedDamages = false;
        $payment->reducedFeeUniversalCredit = false;
        $payment->reducedFeeLowIncome = false;

        $lpa = $this->createLpa($payment);

        $this->form
            ->expects($this->once())
            ->method('bind')
            ->with(['reductionOptions' => 'notApply']);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function getRendersTemplateWithCorrectVariables(): void
    {
        $lpa = $this->createLpa();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/fee-reduction/index.twig',
                $this->callback(function (array $params): bool {
                    return isset($params['form'])
                        && isset($params['reductionOptions'])
                        && count($params['reductionOptions']) === 4
                        && array_key_exists('reducedFeeReceivesBenefits', $params['reductionOptions'])
                        && array_key_exists('reducedFeeUniversalCredit', $params['reductionOptions'])
                        && array_key_exists('reducedFeeLowIncome', $params['reductionOptions'])
                        && array_key_exists('notApply', $params['reductionOptions']);
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    // ==================== POST Invalid Tests ====================

    #[Test]
    public function postInvalidRendersForm(): void
    {
        $lpa = $this->createLpa();

        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['reductionOptions' => 'notApply'], $lpa)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    // ==================== POST Valid Tests ====================

    /**
     * @return array<string, array{string, array<string, bool|null>}>
     */
    public static function reductionOptionsProvider(): array
    {
        return [
            'reducedFeeReceivesBenefits' => [
                'reducedFeeReceivesBenefits',
                [
                    'reducedFeeReceivesBenefits' => true,
                    'reducedFeeAwardedDamages'   => true,
                    'reducedFeeLowIncome'        => null,
                    'reducedFeeUniversalCredit'  => null,
                ],
            ],
            'reducedFeeUniversalCredit' => [
                'reducedFeeUniversalCredit',
                [
                    'reducedFeeReceivesBenefits' => false,
                    'reducedFeeAwardedDamages'   => null,
                    'reducedFeeLowIncome'        => false,
                    'reducedFeeUniversalCredit'  => true,
                ],
            ],
            'reducedFeeLowIncome' => [
                'reducedFeeLowIncome',
                [
                    'reducedFeeReceivesBenefits' => false,
                    'reducedFeeAwardedDamages'   => null,
                    'reducedFeeLowIncome'        => true,
                    'reducedFeeUniversalCredit'  => false,
                ],
            ],
            'notApply' => [
                'notApply',
                [
                    'reducedFeeReceivesBenefits' => null,
                    'reducedFeeAwardedDamages'   => null,
                    'reducedFeeLowIncome'        => null,
                    'reducedFeeUniversalCredit'  => null,
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('reductionOptionsProvider')]
    public function postValidSetsPaymentAndRedirects(
        string $option,
        array $expectedPaymentData,
    ): void {
        $lpa = $this->createLpa();

        $postData = ['reductionOptions' => $option];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPayment')
            ->with(
                $this->anything(),
                $this->callback(function (Payment $payment) use ($expectedPaymentData): bool {
                    return $payment->reducedFeeReceivesBenefits == $expectedPaymentData['reducedFeeReceivesBenefits']
                        && $payment->reducedFeeAwardedDamages == $expectedPaymentData['reducedFeeAwardedDamages']
                        && $payment->reducedFeeLowIncome == $expectedPaymentData['reducedFeeLowIncome']
                        && $payment->reducedFeeUniversalCredit == $expectedPaymentData['reducedFeeUniversalCredit'];
                })
            )
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout');

        $response = $this->handler->handle(
            $this->createRequest('POST', $postData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/checkout', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function postValidWithNoChangeSkipsSaveAndRedirects(): void
    {
        $payment = new Payment([
            'reducedFeeReceivesBenefits' => null,
            'reducedFeeAwardedDamages'   => null,
            'reducedFeeLowIncome'        => null,
            'reducedFeeUniversalCredit'  => null,
        ]);

        $lpa = $this->createLpa($payment);

        $postData = ['reductionOptions' => 'notApply'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPayment');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout');

        $response = $this->handler->handle(
            $this->createRequest('POST', $postData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function postValidWithChangedOptionSavesAndRedirects(): void
    {
        $payment = new Payment([
            'reducedFeeReceivesBenefits' => null,
            'reducedFeeAwardedDamages'   => null,
            'reducedFeeLowIncome'        => null,
            'reducedFeeUniversalCredit'  => null,
        ]);

        $lpa = $this->createLpa($payment);

        $postData = ['reductionOptions' => 'reducedFeeLowIncome'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPayment')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout');

        $response = $this->handler->handle(
            $this->createRequest('POST', $postData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function postValidApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa();

        $postData = ['reductionOptions' => 'reducedFeeReceivesBenefits'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->method('setPayment')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'API client failed to set payment details for id: 91333263035 in FeeReductionHandler'
        );

        $this->handler->handle(
            $this->createRequest('POST', $postData, $lpa)
        );
    }

    #[Test]
    public function postValidXmlHttpRequestReturnsJsonResponse(): void
    {
        $lpa = $this->createLpa();

        $postData = ['reductionOptions' => 'notApply'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->method('setPayment')
            ->willReturn(true);

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/checkout');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody($postData)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/fee-reduction');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    // ==================== Repeat Application Tests ====================

    #[Test]
    public function getWithRepeatApplicationUsesRepeatFees(): void
    {
        $lpa = $this->createLpa();
        $lpa->repeatCaseNumber = '12345';

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    // For a repeat application, low income fee = half of half = 23
                    // Full fee for repeat = 46
                    $notApply = $params['reductionOptions']['notApply'];
                    $label = $notApply->getOption('label');
                    // Repeat application full fee: 46
                    return str_contains($label, '46');
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    #[Test]
    public function getWithoutRepeatApplicationUsesStandardFees(): void
    {
        $lpa = $this->createLpa();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $notApply = $params['reductionOptions']['notApply'];
                    $label = $notApply->getOption('label');
                    // Standard full fee: 92
                    return str_contains($label, '92');
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    // ==================== Edge Cases ====================

    #[Test]
    public function postWithNullParsedBodyHandlesGracefully(): void
    {
        $lpa = $this->createLpa();

        $this->form->method('isValid')->willReturn(false);

        $this->renderer->method('render')->willReturn('rendered-html');

        $flowChecker = $this->createMock(FormFlowChecker::class);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/fee-reduction');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function postDoesNotBindExistingPaymentOnPost(): void
    {
        $payment = new Payment();
        $payment->reducedFeeLowIncome = true;

        $lpa = $this->createLpa($payment);

        $postData = ['reductionOptions' => 'notApply'];

        $this->form
            ->expects($this->never())
            ->method('bind');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService->method('setPayment')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }
}
