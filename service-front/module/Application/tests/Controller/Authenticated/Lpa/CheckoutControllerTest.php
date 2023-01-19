<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Model\Service\Lpa\Communication;
use ApplicationTest\Controller\AbstractControllerTest;
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

class CheckoutControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|Communication
     */
    private $communication;
    /**
     * @var MockInterface|GovPayClient
     */
    private $govPayClient;
    /**
     * @var MockInterface|BlankMainFlowForm
     */
    private $blankMainFlowForm;
    /**
     * @var MockInterface|ElementInterface
     */
    private $submitButton;

    public function setUp(): void
    {
        parent::setUp();

        $this->blankMainFlowForm = Mockery::mock(BlankMainFlowForm::class);
        $this->submitButton = Mockery::mock(ElementInterface::class);
    }

    /**
     * @param string $controllerName
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

    public function testIndexActionGet()
    {
        $controller = $this->getController(CheckoutController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setPayByCardExpectations('Confirm and pay by card');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals(41, $result->getVariable('lowIncomeFee'));
        $this->assertEquals(82, $result->getVariable('fullFee'));
    }

    public function testIndexActionPostIncompleteLpa()
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->setRedirectToRoute('lpa/more-info-required', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testChequeActionIncompleteLpa()
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $this->setRedirectToRoute('lpa/more-info-required', $this->lpa, $response);

        $result = $controller->chequeAction();

        $this->assertEquals($response, $result);
    }

    public function testChequeActionFailed()
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = 82;
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa, $this->lpa->payment])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set payment details for id: 91333263035 in CheckoutController');

        $controller->chequeAction();
    }

    public function testChequeActionIncorrectAmountFailed()
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = 182;
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa, $this->lpa->payment])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set payment details for id: 91333263035 in CheckoutController');

        $controller->chequeAction();
    }

    public function testChequeActionSuccess()
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();

        $this->lpa->payment->method = null;
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa, $this->lpa->payment])->andReturn(true)->twice();
        $this->lpaApplicationService->shouldReceive('lockLpa')
            ->withArgs([$this->lpa])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/complete', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $controller->chequeAction();

        $this->assertEquals($response, $result);
    }

    public function testConfirmActionIncompleteLpa()
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $this->setRedirectToRoute('lpa/more-info-required', $this->lpa, $response);

        $result = $controller->confirmAction();

        $this->assertEquals($response, $result);
    }

    public function testConfirmActionInvalidAmount()
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = 82;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid option');

        $controller->confirmAction();
    }

    public function testConfirmActionSuccess()
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();

        $this->lpa->payment->amount = 0;
        $this->lpa->payment->reducedFeeUniversalCredit = true;
        $this->lpa->completedAt = null;

        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/complete', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $controller->confirmAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionIncompleteLpa()
    {
        $controller = $this->getController(CheckoutController::class);

        $response = new Response();
        $controller->dispatch($this->request, $response);

        $this->setRedirectToRoute('lpa/more-info-required', $this->lpa, $response);

        $result = $controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionNoExistingPayment()
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
        $payment->shouldReceive('offsetGet')->with('payment_id')->andReturn('PAYMENT COMPLETE')->once();
        $payment->shouldReceive('getPaymentPageUrl')->andReturn($responseUrl)->once();

        $this->govPayClient->shouldReceive('createPayment')->andReturn($payment)->once();

        $this->lpaApplicationService->shouldReceive('updateApplication')->andReturn(true)->once();

        $this->redirect->shouldReceive('toUrl')->withArgs([$responseUrl])->andReturn($response)->once();

        $result = $controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionExistingPaymentNull()
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

    public function testPayActionExistingPaymentSuccessful()
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
        $payment->shouldReceive('isSuccess')->andReturn(true)->twice();
        $payment->shouldReceive('offsetGet')->with('reference')->andReturn('existing')->once();
        $payment->shouldReceive('offsetGet')->with('email')->andReturn('unit@TEST.com')->once();

        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->twice();

        $this->lpaApplicationService->shouldReceive('updateApplication')->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/complete', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionExistingPaymentNotFinished()
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
        $this->redirect->shouldReceive('toUrl')->withArgs(['http://unit.test.com'])->andReturn($response)->once();

        $result = $controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionExistingPaymentFinishedNotSuccessful()
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
        $payment->shouldReceive('offsetGet')->with('payment_id')->andReturn('PAYMENT COMPLETE')->once();
        $payment->shouldReceive('getPaymentPageUrl')->andReturn($responseUrl)->once();

        $this->govPayClient->shouldReceive('createPayment')->andReturn($payment)->once();

        $this->lpaApplicationService->shouldReceive('updateApplication')->andReturn(true)->once();

        $this->redirect->shouldReceive('toUrl')->withArgs([$responseUrl])->andReturn($response)->once();

        $result = $controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayResponseActionNoGatewayReference()
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->gatewayReference = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment id needed');

        $controller->payResponseAction();
    }

    public function testPayResponseActionNotSuccessfulCancelled()
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->gatewayReference = 'unsuccessful';

        $payment = Mockery::mock(GovPayPayment::class);
        $payment->shouldReceive('isSuccess')->andReturn(false)->once();
        $payment->shouldReceive('getStateCode')->andReturn('P0030')->once();

        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->once();

        $this->setPayByCardExpectations('Retry online payment');

        /** @var ViewModel $result */
        $result = $controller->payResponseAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/checkout/govpay-cancel.twig', $result->getTemplate());
    }

    public function testPayResponseActionNotSuccessfulOther()
    {
        $controller = $this->getController(CheckoutController::class);

        $this->lpa->payment->gatewayReference = 'unsuccessful';

        $payment = Mockery::mock(GovPayPayment::class);
        $payment->shouldReceive('isSuccess')->andReturn(false)->once();
        $payment->shouldReceive('getStateCode')->andReturn('OTHER')->once();

        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->once();

        $this->setPayByCardExpectations('Retry online payment');

        /** @var ViewModel $result */
        $result = $controller->payResponseAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/checkout/govpay-failure.twig', $result->getTemplate());
    }

    private function setPayByCardExpectations($submitButtonValue)
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
