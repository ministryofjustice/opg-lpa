<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Payment\Helper\LpaIdHelper;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Opg\Lpa\DataModel\Lpa\Payment\Payment as LpaPayment;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Form\ElementInterface;
use Zend\Http\Response;
use Zend\Stdlib\ArrayObject;
use Zend\View\Model\ViewModel;
use Alphagov\Pay\Client as GovPayClient;
use Alphagov\Pay\Response\Payment as GovPayPayment;

class CheckoutControllerTest extends AbstractControllerTest
{
    /**
     * @var CheckoutController
     */
    private $controller;
    /**
     * @var Lpa
     */
    private $lpa;
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

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(CheckoutController::class);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->lpa = FixturesData::getHwLpa();

        $this->communication = Mockery::mock(Communication::class);
        $this->controller->setCommunicationService($this->communication);

        $this->govPayClient = Mockery::mock(GovPayClient::class);
        $this->controller->setPaymentClient($this->govPayClient);

        $this->blankMainFlowForm = Mockery::mock(BlankMainFlowForm::class);
        $this->submitButton = Mockery::mock(ElementInterface::class);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->request->shouldReceive('isPost')->andReturn(true)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setPayByCardExpectations('Confirm and pay by card');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals(41, $result->getVariable('lowIncomeFee'));
        $this->assertEquals(82, $result->getVariable('fullFee'));
    }

    public function testIndexActionPostIncompleteLpa()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $lpa = new Lpa();
        $lpa->id = 123;
        $this->controller->setLpa($lpa);
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->setRedirectToRoute('lpa/more-info-required', $lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testChequeActionIncompleteLpa()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $lpa = new Lpa();
        $lpa->id = 123;
        $this->controller->setLpa($lpa);
        $this->setRedirectToRoute('lpa/more-info-required', $lpa, $response);

        $result = $this->controller->chequeAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set payment details for id: 5531003156 in CheckoutController
     */
    public function testChequeActionFailed()
    {
        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = 82;
        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa->id, $this->lpa->payment])->andReturn(false)->once();

        $this->controller->chequeAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set payment details for id: 5531003156 in CheckoutController
     */
    public function testChequeActionIncorrectAmountFailed()
    {
        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = 182;
        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa->id, $this->lpa->payment])->andReturn(false)->once();

        $this->controller->chequeAction();
    }

    public function testChequeActionSuccess()
    {
        $response = new Response();

        $this->lpa->payment->method = null;
        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa->id, $this->lpa->payment])->andReturn(true)->twice();
        $this->lpaApplicationService->shouldReceive('lockLpa')
            ->withArgs([$this->lpa->id])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/complete', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $this->controller->chequeAction();

        $this->assertEquals($response, $result);
    }

    public function testConfirmActionIncompleteLpa()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $lpa = new Lpa();
        $lpa->id = 123;
        $this->controller->setLpa($lpa);
        $this->setRedirectToRoute('lpa/more-info-required', $lpa, $response);

        $result = $this->controller->confirmAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid option
     */
    public function testConfirmActionInvalidAmount()
    {
        $this->lpa->payment->method = null;
        $this->lpa->payment->amount = 82;
        $this->controller->setLpa($this->lpa);

        $this->controller->confirmAction();
    }

    public function testConfirmActionSuccess()
    {
        $response = new Response();

        $this->lpa->payment->amount = 0;
        $this->lpa->payment->reducedFeeUniversalCredit = true;
        $this->lpa->completedAt = null;

        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa->id])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/complete', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $this->controller->confirmAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionIncompleteLpa()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $lpa = new Lpa();
        $lpa->id = 123;
        $this->controller->setLpa($lpa);
        $this->setRedirectToRoute('lpa/more-info-required', $lpa, $response);

        $result = $this->controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionNoExistingPayment()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $this->lpa->payment->method = null;
        $this->controller->setLpa($this->lpa);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa->id, $this->lpa->payment])->andReturn(true)->once();
        $responseUrl = "lpa/{$this->lpa->id}/checkout/pay/response";
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/checkout/pay/response', ['lpa-id' => $this->lpa->id]])->andReturn($responseUrl)->once();
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('createPayment')
            ->withArgs(function ($amount, $reference, $description, $returnUrl) {
                /** @var Uri $returnUrl */
                return $amount === (int)($this->lpa->payment->amount * 100)
                    && strpos($reference, LpaIdHelper::padLpaId($this->lpa->id) . '-') === 0
                    && $description === "Health and welfare LPA for {$this->lpa->document->donor->name}"
                    && $returnUrl->getPath() === "/{$this->lpa->id}/checkout/pay/response";
            })->andReturn($payment)->once();
        $payment->payment_id = 'PAYMENT COMPLETE';
        $this->lpaApplicationService->shouldReceive('updatePayment')->withArgs([$this->lpa])->andReturn(true)->once();
        $payment->shouldReceive('getPaymentPageUrl')->andReturn($responseUrl)->once();
        $this->redirect->shouldReceive('toUrl')->withArgs([$responseUrl])->andReturn($response)->once();

        $result = $this->controller->payAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid GovPay payment reference: existing
     */
    public function testPayActionExistingPaymentNull()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        Calculator::calculate($this->lpa);
        $this->lpa->payment->method = null;
        $this->lpa->payment->gatewayReference = 'existing';
        $this->controller->setLpa($this->lpa);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn(null)->once();

        $this->controller->payAction();
    }

    public function testPayActionExistingPaymentSuccessful()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        Calculator::calculate($this->lpa);
        $this->lpa->payment->method = null;
        $this->lpa->payment->gatewayReference = 'existing';
        $this->controller->setLpa($this->lpa);
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
        $this->lpaApplicationService->shouldReceive('updatePayment')->withArgs(function ($lpa) {
            return $lpa->payment->method === LpaPayment::PAYMENT_TYPE_CARD
                && $lpa->payment->reference = 'existing'
                && $lpa->payment->date instanceof DateTime
                && $lpa->payment->email->address === 'unit@test.com';
        });
        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa->id])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/complete', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $this->controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionExistingPaymentNotFinished()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        Calculator::calculate($this->lpa);
        $this->lpa->payment->method = null;
        $this->lpa->payment->gatewayReference = 'existing';
        $this->controller->setLpa($this->lpa);
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

        $result = $this->controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionExistingPaymentFinishedNotSuccessful()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        Calculator::calculate($this->lpa);
        $this->lpa->payment->method = null;
        $this->lpa->payment->gatewayReference = 'existing';
        $this->controller->setLpa($this->lpa);
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
        $this->govPayClient->shouldReceive('createPayment')
            ->withArgs(function ($amount, $reference, $description, $returnUrl) {
                /** @var Uri $returnUrl */
                return $amount === (int)($this->lpa->payment->amount * 100)
                    && strpos($reference, LpaIdHelper::padLpaId($this->lpa->id) . '-') === 0
                    && $description === "Health and welfare LPA for {$this->lpa->document->donor->name}"
                    && $returnUrl->getPath() === "/{$this->lpa->id}/checkout/pay/response";
            })->andReturn($payment)->once();
        $payment->payment_id = 'PAYMENT COMPLETE';
        $this->lpaApplicationService->shouldReceive('updatePayment')->withArgs([$this->lpa])->andReturn(true)->once();
        $payment->shouldReceive('getPaymentPageUrl')->andReturn($responseUrl)->once();
        $this->redirect->shouldReceive('toUrl')->withArgs([$responseUrl])->andReturn($response)->once();

        $result = $this->controller->payAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Payment id needed
     */
    public function testPayResponseActionNoGatewayReference()
    {
        $this->lpa->payment->gatewayReference = null;
        $this->controller->setLpa($this->lpa);

        $this->controller->payResponseAction();
    }

    public function testPayResponseActionNotSuccessfulCancelled()
    {
        $this->lpa->payment->gatewayReference = 'unsuccessful';
        $this->controller->setLpa($this->lpa);
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->once();
        $payment->shouldReceive('isSuccess')->andReturn(false)->once();
        $payment->state = new ArrayObject();
        $payment->state->code = 'P0030';
        $this->setPayByCardExpectations('Retry online payment');

        /** @var ViewModel $result */
        $result = $this->controller->payResponseAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/checkout/govpay-cancel.twig', $result->getTemplate());
    }

    public function testPayResponseActionNotSuccessfulOther()
    {
        $this->lpa->payment->gatewayReference = 'unsuccessful';
        $this->controller->setLpa($this->lpa);
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('getPayment')
            ->withArgs([$this->lpa->payment->gatewayReference])->andReturn($payment)->once();
        $payment->shouldReceive('isSuccess')->andReturn(false)->once();
        $payment->state = new ArrayObject();
        $payment->state->code = 'OTHER';
        $this->setPayByCardExpectations('Retry online payment');

        /** @var ViewModel $result */
        $result = $this->controller->payResponseAction();

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
        $this->blankMainFlowForm->shouldReceive('get')
            ->withArgs(['submit'])->andReturn($this->submitButton)->once();
        $this->submitButton->shouldReceive('setAttribute')
            ->withArgs(['value', $submitButtonValue])
            ->andReturn($this->submitButton)->once();
    }
}
