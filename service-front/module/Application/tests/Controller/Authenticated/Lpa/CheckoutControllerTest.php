<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Model\Service\Lpa\Communication;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\Diactoros\Response\RedirectResponse;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use RuntimeException;
use Laminas\Form\ElementInterface;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayObject;
use Laminas\View\Model\ViewModel;
use Alphagov\Pay\Client as GovPayClient;
use Alphagov\Pay\Response\Payment as GovPayPayment;

final class CheckoutControllerTest extends AbstractControllerTestCase
{
    private MockInterface|Communication $communication;
    private MockInterface|GovPayClient $govPayClient;
    private MockInterface|BlankMainFlowForm $blankMainFlowForm;
    private MockInterface|ElementInterface $submitButton;
    private int $fee;

    public function setUp(): void
    {
        parent::setUp();

        $this->blankMainFlowForm = Mockery::mock(BlankMainFlowForm::class);
        $this->submitButton = Mockery::mock(ElementInterface::class);
        $this->fee = 92;
    }

    /**
     * @return CheckoutController
     */
    protected function getController(string $controllerName)
    {
        $controller = parent::getController($controllerName);

        $this->communication = Mockery::mock(Communication::class);
        $controller->setCommunicationService($this->communication);

        $this->govPayClient = Mockery::mock(GovPayClient::class);
        $controller->setPaymentClient($this->govPayClient);

        return $controller;
    }

    public function testIndexActionGet(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setPayByCardExpectations('Confirm and pay by card');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals(46, $result->getVariable('lowIncomeFee'));
        $this->assertEquals($this->fee, $result->getVariable('fullFee'));
    }

    public function testIndexActionPostIncompleteLpa(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $result = $controller->indexAction();
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testChequeActionIncompleteLpa(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $result = $controller->chequeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testChequeActionFailed(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = $this->fee;
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa, $this->lpa->payment])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set payment details for id: 91333263035 in CheckoutController');

        $controller->chequeAction();
    }

    public function testChequeActionIncorrectAmountFailed(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = $this->fee + 100;
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa, $this->lpa->payment])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set payment details for id: 91333263035 in CheckoutController');

        $controller->chequeAction();
    }

    public function testChequeActionSuccess(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->method = null;
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa, $this->lpa->payment])->andReturn(true)->twice();
        $this->lpaApplicationService->shouldReceive('lockLpa')
            ->withArgs([$this->lpa])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $result = $controller->chequeAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/complete', $location);
        $this->assertStringContainsString((string) $this->lpa->id, $location);
    }

    public function testConfirmActionIncompleteLpa(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $result = $controller->confirmAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testConfirmActionInvalidAmount(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = $this->fee;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid option');

        $controller->confirmAction();
    }

    public function testConfirmActionSuccess(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->amount = 0;
        $this->lpa->payment->reducedFeeUniversalCredit = true;
        $this->lpa->completedAt = null;

        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $result = $controller->confirmAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/complete', $location);
        $this->assertStringContainsString((string) $this->lpa->id, $location);
    }

    public function testPayActionIncompleteLpa(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $result = $controller->payAction();

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testPayActionNoExistingPayment(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $this->lpa->payment->method = null;
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa, $this->lpa->payment])->andReturn(true)->once();
        $responseUrl = "lpa/{$this->lpa->id}/checkout/pay/response";
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/checkout/pay/response', ['lpa-id' => $this->lpa->id]])->andReturn($responseUrl)->once();
        $payment = Mockery::mock(GovPayPayment::class);

        $this->govPayClient->shouldReceive('createPayment')->andReturn($payment)->once();

        $payment->payment_id = 'PAYMENT COMPLETE';
        $this->lpaApplicationService->shouldReceive('updateApplication')->andReturn(true)->once();
        $payment->shouldReceive('getPaymentPageUrl')->andReturn($responseUrl)->once();
        $result = $controller->payAction();

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals($responseUrl, $result->getHeaderLine('Location'));
    }

    public function testPayActionExistingPaymentNull(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        Calculator::calculate($this->lpa);
        $this->lpa->payment->method = null;
        $this->lpa->payment->gatewayReference = 'existing';
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn(null)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid GovPay payment reference: existing');

        $controller->payAction();
    }

    public function testPayActionExistingPaymentSuccessful(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        Calculator::calculate($this->lpa);
        $this->lpa->payment->method = null;
        $this->lpa->payment->gatewayReference = 'existing';
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->twice();
        $payment->shouldReceive('isSuccess')->andReturn(true)->twice();
        $payment->reference = 'existing';
        $payment->email = 'unit@TEST.com';
        $this->lpaApplicationService->shouldReceive('updateApplication')->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $result = $controller->payAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/complete', $location);
        $this->assertStringContainsString((string) $this->lpa->id, $location);
    }

    public function testPayActionExistingPaymentNotFinished(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        Calculator::calculate($this->lpa);
        $this->lpa->payment->method = null;
        $this->lpa->payment->gatewayReference = 'existing';
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->once();
        $payment->shouldReceive('isSuccess')->andReturn(false)->once();
        $payment->shouldReceive('isFinished')->andReturn(false)->once();
        $payment->shouldReceive('getPaymentPageUrl')->andReturn('http://unit.test.com')->once();

        $result = $controller->payAction();

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('http://unit.test.com', $result->getHeaderLine('Location'));
    }

    public function testPayActionExistingPaymentFinishedNotSuccessful(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        Calculator::calculate($this->lpa);
        $this->lpa->payment->method = null;
        $this->lpa->payment->gatewayReference = 'existing';
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->once();
        $payment->shouldReceive('isSuccess')->andReturn(false)->once();
        $payment->shouldReceive('isFinished')->andReturn(true)->once();

        $responseUrl = "lpa/{$this->lpa->id}/checkout/pay/response";
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/checkout/pay/response', ['lpa-id' => $this->lpa->id]])->andReturn($responseUrl)->once();
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('createPayment')->andReturn($payment)->once();
        $payment->payment_id = 'PAYMENT COMPLETE';
        $this->lpaApplicationService->shouldReceive('updateApplication')->andReturn(true)->once();
        $payment->shouldReceive('getPaymentPageUrl')->andReturn($responseUrl)->once();

        $result = $controller->payAction();

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals($responseUrl, $result->getHeaderLine('Location'));
    }

    public function testPayResponseActionNoGatewayReference(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->gatewayReference = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment id needed');

        $controller->payResponseAction();
    }

    public function testPayResponseActionNotSuccessfulCancelled(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->gatewayReference = 'unsuccessful';
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->once();
        $payment->shouldReceive('isSuccess')->andReturn(false)->once();
        $payment->state = new ArrayObject();
        $payment->state->code = 'P0030';
        $this->setPayByCardExpectations('Retry online payment');

        /** @var ViewModel $result */
        $result = $controller->payResponseAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/checkout/govpay-cancel.twig', $result->getTemplate());
    }

    public function testPayResponseActionNotSuccessfulOther(): void
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->gatewayReference = 'unsuccessful';
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->once();
        $payment->shouldReceive('isSuccess')->andReturn(false)->once();
        $payment->state = new ArrayObject();
        $payment->state->code = 'OTHER';
        $this->setPayByCardExpectations('Retry online payment');

        /** @var ViewModel $result */
        $result = $controller->payResponseAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/checkout/govpay-failure.twig', $result->getTemplate());
    }

    private function setPayByCardExpectations(string $submitButtonValue): void
    {
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/checkout/pay', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/checkout/pay")->once();
        $this->blankMainFlowForm->shouldReceive('setAttribute')
            ->withArgs(['action', "lpa/{$this->lpa->id}/checkout/pay"])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->blankMainFlowForm->shouldReceive('setAttribute')
            ->withArgs(['class', 'js-single-use'])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->submitButton->shouldReceive('setAttribute')
            ->withArgs(['value', $submitButtonValue])
            ->andReturn($this->submitButton)->once();
        if ($submitButtonValue == 'Confirm and pay by card') {
            $this->blankMainFlowForm->shouldReceive('get')
                ->withArgs(['submit'])->andReturn($this->submitButton)->twice();
            $this->submitButton->shouldReceive('setAttribute')
                ->withArgs(['data-cy', 'confirm-and-pay-by-card'])
                ->andReturn($this->submitButton)->once();
        } else {
            $this->blankMainFlowForm->shouldReceive('get')
              ->withArgs(['submit'])->andReturn($this->submitButton)->once();
        }
    }
}
