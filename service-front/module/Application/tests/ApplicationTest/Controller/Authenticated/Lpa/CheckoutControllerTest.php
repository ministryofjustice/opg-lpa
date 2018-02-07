<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Form\Lpa\PaymentForm;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Payment\Helper\LpaIdHelper;
use Application\Model\Service\Payment\Payment;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Mockery;
use Mockery\MockInterface;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\ResponseInterface;
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
     * @var MockInterface|PaymentForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    /**
     * @var MockInterface|Payment
     */
    private $payment;
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

        $this->form = Mockery::mock(PaymentForm::class);
        $this->lpa = FixturesData::getHwLpa();

        $this->payment = Mockery::mock(Payment::class);
        $this->controller->setPaymentService($this->payment);

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
        $this->cache->shouldReceive('getItem')->withArgs(['worldpay-percentage'])->andReturn(100)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->once();
        $this->setPayByCardExpectations('Confirm and pay by card');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($this->form, $result->getVariable('worldPayForm'));
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

    public function testIndexActionInvalidWorldPayPercentage()
    {
        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        //Will default to zero so worldpay form shouldn't be retrieved
        $this->cache->shouldReceive('getItem')
            ->withArgs(['worldpay-percentage'])->andReturn('')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->never();
        $this->setPayByCardExpectations('Confirm and pay by card');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals(41, $result->getVariable('lowIncomeFee'));
        $this->assertEquals(82, $result->getVariable('fullFee'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->lpa->payment->method = null;
        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->cache->shouldReceive('getItem')->withArgs(['worldpay-percentage'])->andReturn(100)->once();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->once();
        $this->setPostInvalid($this->form, [], null, 2);
        $this->setPayByCardExpectations('Confirm and pay by card');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($this->form, $result->getVariable('worldPayForm'));
        $this->assertEquals(41, $result->getVariable('lowIncomeFee'));
        $this->assertEquals(82, $result->getVariable('fullFee'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set payment details for id: 5531003156 in CheckoutController
     */
    public function testIndexActionPostFailed()
    {
        $postData = [];

        $this->lpa->payment->method = null;
        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->cache->shouldReceive('getItem')
            ->withArgs(['worldpay-percentage'])->andReturn(100)->once();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->once();
        $this->setPostValid($this->form, $postData, null, 2);
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa->id, $this->lpa->payment])->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $postData = [
            'email' => 'test@unit.com'
        ];

        $this->lpa->payment->method = null;
        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->cache->shouldReceive('getItem')->withArgs(['worldpay-percentage'])->andReturn(100)->once();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->once();
        $this->setPostValid($this->form, $postData, null, 2);
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa->id, $this->lpa->payment])->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $options = [];
        $this->payment->shouldReceive('getOptions')
            ->withArgs([$this->lpa, $postData['email']])->andReturn($options)->once();
        $gateway = Mockery::mock(GatewayInterface::class);
        $this->payment->shouldReceive('getGateway')->andReturn($gateway)->once();
        $purchase = Mockery::mock(RequestInterface::class);
        $gateway->shouldReceive('purchase')->withArgs([$options])->andReturn($purchase)->once();
        $purchaseResponse = Mockery::mock(ResponseInterface::class);
        $purchase->shouldReceive('send')->andReturn($purchaseResponse)->once();
        $purchaseResponse->shouldReceive('getData')->andReturn(new ArrayObject())->once();
        $redirectUrl = '';
        foreach (['success', 'failure', 'cancel'] as $type) {
            $this->url->shouldReceive('fromRoute')
                ->withArgs(['lpa/checkout/worldpay/return/' . $type, ['lpa-id' => $this->lpa->id]])
                ->andReturn("lpa/{$this->lpa->id}/checkout/worldpay/return/{$type}");
            $redirectUrl .= "&{$type}URL=http://lpa/{$this->lpa->id}/checkout/worldpay/return/{$type}";
        }
        $this->redirect->shouldReceive('toUrl')->withArgs([$redirectUrl])->andReturn($response)->once();

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
        $this->assertEquals('application/checkout/govpay-cancel.twig', $result->getTemplate());
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
        $this->assertEquals('application/checkout/govpay-failure.twig', $result->getTemplate());
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Invalid success response from Worldpay.
     * Expected paymentStatus parameter was not found. http://unit.test.com
     */
    public function testWorldpaySuccessActionMissingParameters()
    {
        $this->request->shouldReceive('getQuery')->withArgs(['paymentStatus'])->andReturn(null)->once();
        $_SERVER["REQUEST_URI"] = 'http://unit.test.com';

        $this->controller->worldpaySuccessAction();
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Invalid success response from Worldpay. paymentStatus was value (expected AUTHORISED)
     */
    public function testWorldpaySuccessActionNotAuthorised()
    {
        foreach (['paymentStatus', 'orderKey', 'paymentAmount', 'paymentCurrency', 'mac'] as $param) {
            $this->request->shouldReceive('getQuery')->withArgs([$param])->andReturn('value')->twice();
        }

        $this->controller->worldpaySuccessAction();
    }

    public function testWorldpaySuccessActionAuthorised()
    {
        $response = new Response();

        $params = ['paymentStatus' => 'AUTHORISED'];
        $this->request->shouldReceive('getQuery')->withArgs(['paymentStatus'])->andReturn('AUTHORISED')->twice();
        foreach (['orderKey', 'paymentAmount', 'paymentCurrency', 'mac'] as $param) {
            $this->request->shouldReceive('getQuery')->withArgs([$param])->andReturn('value')->twice();
            $params[$param] = 'value';
        }
        $this->controller->setLpa($this->lpa);
        $this->payment->shouldReceive('verifyMacString')->withArgs([$params])->once();
        $this->payment->shouldReceive('verifyOrderKey')->withArgs([$params, $this->lpa->id])->once();
        $this->payment->shouldReceive('updateLpa')->withArgs([$params, $this->lpa])->once();

        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa->id])->andReturn(true)->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->withArgs([$this->lpa])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/complete', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $this->controller->worldpaySuccessAction();

        $this->assertEquals($response, $result);
    }

    public function testWorldpayCancelActionGet()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $postData = [
            'email' => 'test@unit.com'
        ];

        Calculator::calculate($this->lpa);
        $this->controller->setLpa($this->lpa);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->worldpayCancelAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('worldPayForm'));
    }

    public function testWorldpayCancelActionPostSuccess()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $postData = [
            'email' => 'test@unit.com'
        ];

        Calculator::calculate($this->lpa);
        $this->controller->setLpa($this->lpa);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->once();
        $this->setPostValid($this->form, $postData);
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa->id, $this->lpa->payment])->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $options = [];
        $this->payment->shouldReceive('getOptions')
            ->withArgs([$this->lpa, $postData['email']])->andReturn($options)->once();
        $gateway = Mockery::mock(GatewayInterface::class);
        $this->payment->shouldReceive('getGateway')->andReturn($gateway)->once();
        $purchase = Mockery::mock(RequestInterface::class);
        $gateway->shouldReceive('purchase')->withArgs([$options])->andReturn($purchase)->once();
        $purchaseResponse = Mockery::mock(ResponseInterface::class);
        $purchase->shouldReceive('send')->andReturn($purchaseResponse)->once();
        $purchaseResponse->shouldReceive('getData')->andReturn(new ArrayObject())->once();
        $redirectUrl = '';
        foreach (['success', 'failure', 'cancel'] as $type) {
            $this->url->shouldReceive('fromRoute')
                ->withArgs(['lpa/checkout/worldpay/return/' . $type, ['lpa-id' => $this->lpa->id]])
                ->andReturn("lpa/{$this->lpa->id}/checkout/worldpay/return/{$type}");
            $redirectUrl .= "&{$type}URL=http://lpa/{$this->lpa->id}/checkout/worldpay/return/{$type}";
        }
        $this->redirect->shouldReceive('toUrl')->withArgs([$redirectUrl])->andReturn($response)->once();

        $result = $this->controller->worldpayCancelAction();

        $this->assertEquals($response, $result);
    }

    public function testWorldpayFailureActionGet()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $postData = [
            'email' => 'test@unit.com'
        ];

        Calculator::calculate($this->lpa);
        $this->controller->setLpa($this->lpa);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->worldpayFailureAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('worldPayForm'));
    }

    public function testWorldpayFailureActionPostSuccess()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $postData = [
            'email' => 'test@unit.com'
        ];

        Calculator::calculate($this->lpa);
        $this->controller->setLpa($this->lpa);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PaymentForm'])->andReturn($this->form)->once();
        $this->setPostValid($this->form, $postData);
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs([$this->lpa->id, $this->lpa->payment])->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $options = [];
        $this->payment->shouldReceive('getOptions')
            ->withArgs([$this->lpa, $postData['email']])->andReturn($options)->once();
        $gateway = Mockery::mock(GatewayInterface::class);
        $this->payment->shouldReceive('getGateway')->andReturn($gateway)->once();
        $purchase = Mockery::mock(RequestInterface::class);
        $gateway->shouldReceive('purchase')->withArgs([$options])->andReturn($purchase)->once();
        $purchaseResponse = Mockery::mock(ResponseInterface::class);
        $purchase->shouldReceive('send')->andReturn($purchaseResponse)->once();
        $purchaseResponse->shouldReceive('getData')->andReturn(new ArrayObject())->once();
        $redirectUrl = '';
        foreach (['success', 'failure', 'cancel'] as $type) {
            $this->url->shouldReceive('fromRoute')
                ->withArgs(['lpa/checkout/worldpay/return/' . $type, ['lpa-id' => $this->lpa->id]])
                ->andReturn("lpa/{$this->lpa->id}/checkout/worldpay/return/{$type}");
            $redirectUrl .= "&{$type}URL=http://lpa/{$this->lpa->id}/checkout/worldpay/return/{$type}";
        }
        $this->redirect->shouldReceive('toUrl')->withArgs([$redirectUrl])->andReturn($response)->once();

        $result = $this->controller->worldpayFailureAction();

        $this->assertEquals($response, $result);
    }

    public function testWorldpayPendingActionGet()
    {
        $result = $this->controller->worldpayPendingAction();

        $this->assertNull($result);
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
