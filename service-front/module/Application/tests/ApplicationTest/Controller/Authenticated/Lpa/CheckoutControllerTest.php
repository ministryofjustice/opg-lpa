<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Form\Lpa\PaymentForm;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Payment\Helper\LpaIdHelper;
use Application\Model\Service\Payment\Payment;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Mockery;
use Mockery\MockInterface;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\ResponseInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment as LpaPayment;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Log\Writer\Mock;
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

    public function setUp()
    {
        $this->controller = new CheckoutController();
        parent::controllerSetUp($this->controller);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->form = Mockery::mock(PaymentForm::class);
        $this->lpa = FixturesData::getHwLpa();

        $this->payment = Mockery::mock(Payment::class);
        $this->serviceLocator->shouldReceive('get')->with('Payment')->andReturn($this->payment);

        $this->communication = Mockery::mock(Communication::class);
        $this->serviceLocator->shouldReceive('get')->with('Communication')->andReturn($this->communication);

        $this->govPayClient = Mockery::mock(GovPayClient::class);
        $this->serviceLocator->shouldReceive('get')->with('GovPayClient')->andReturn($this->govPayClient);
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
        $this->cache->shouldReceive('getItem')->with('worldpay-percentage')->andReturn(100)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\PaymentForm')->andReturn($this->form)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('worldpayForm'));
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
        $this->redirect->shouldReceive('toRoute')->with('lpa/more-info-required', ['lpa-id' => $lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionInvalidWorldPayPercentage()
    {
        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->cache->shouldReceive('getItem')->with('worldpay-percentage')->andReturn('')->once(); //Will default to zero so worldpay form shouldn't be retrieved
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\PaymentForm')->andReturn($this->form)->never();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(41, $result->getVariable('lowIncomeFee'));
        $this->assertEquals(82, $result->getVariable('fullFee'));
    }

    public function testIndexActionPostInvalid()
    {
        $postData = [];

        $this->lpa->payment->method = null;
        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->cache->shouldReceive('getItem')->with('worldpay-percentage')->andReturn(100)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\PaymentForm')->andReturn($this->form)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('worldpayForm'));
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
        $this->cache->shouldReceive('getItem')->with('worldpay-percentage')->andReturn(100)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\PaymentForm')->andReturn($this->form)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')->with($this->lpa->id, $this->lpa->payment)->andReturn(false)->once();

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
        $this->cache->shouldReceive('getItem')->with('worldpay-percentage')->andReturn(100)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\PaymentForm')->andReturn($this->form)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')->with($this->lpa->id, $this->lpa->payment)->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $options = [];
        $this->payment->shouldReceive('getOptions')->with($this->lpa, $postData['email'])->andReturn($options)->once();
        $gateway = Mockery::mock(GatewayInterface::class);
        $this->payment->shouldReceive('getGateway')->andReturn($gateway)->once();
        $purchase = Mockery::mock(RequestInterface::class);
        $gateway->shouldReceive('purchase')->with($options)->andReturn($purchase)->once();
        $purchaseResponse = Mockery::mock(ResponseInterface::class);
        $purchase->shouldReceive('send')->andReturn($purchaseResponse)->once();
        $purchaseResponse->shouldReceive('getData')->andReturn(new ArrayObject())->once();
        $redirectUrl = '';
        foreach (['success', 'failure', 'cancel'] as $type) {
            $this->url->shouldReceive('fromRoute')
                ->with('lpa/checkout/worldpay/return/' . $type, ['lpa-id' => $this->lpa->id])
                ->andReturn("lpa/{$this->lpa->id}/checkout/worldpay/return/{$type}");
            $redirectUrl .= "&{$type}URL=http://lpa/{$this->lpa->id}/checkout/worldpay/return/{$type}";
        }
        $this->redirect->shouldReceive('toUrl')->with($redirectUrl)->andReturn($response)->once();

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
        $this->redirect->shouldReceive('toRoute')->with('lpa/more-info-required', ['lpa-id' => $lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

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
        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('setPayment')->with($this->lpa->id, $this->lpa->payment)->andReturn(false)->once();

        $this->controller->chequeAction();
    }

    public function testChequeActionSuccess()
    {
        $response = new Response();

        $this->lpa->payment->method = null;
        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('setPayment')->with($this->lpa->id, $this->lpa->payment)->andReturn(true)->twice();
        $this->lpaApplicationService->shouldReceive('lockLpa')->with($this->lpa->id)->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->with('lpa/view-docs', ['lpa-id' => $this->lpa->id], ['force_canonical' => true])->andReturn("lpa/{$this->lpa->id}/view-docs")->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->with($this->lpa, "lpa/{$this->lpa->id}/view-docs")->once();
        $this->redirect->shouldReceive('toRoute')->with('lpa/complete', ['lpa-id' => $this->lpa->id])->andReturn($response)->once();

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
        $this->redirect->shouldReceive('toRoute')->with('lpa/more-info-required', ['lpa-id' => $lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

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
        $this->lpaApplicationService->shouldReceive('lockLpa')->with($this->lpa->id)->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->with('lpa/view-docs', ['lpa-id' => $this->lpa->id], ['force_canonical' => true])->andReturn("lpa/{$this->lpa->id}/view-docs")->once();
        $this->communication->shouldReceive('sendRegistrationCompleteEmail')->with($this->lpa, "lpa/{$this->lpa->id}/view-docs")->once();
        $this->redirect->shouldReceive('toRoute')->with('lpa/complete', ['lpa-id' => $this->lpa->id])->andReturn($response)->once();

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
        $this->redirect->shouldReceive('toRoute')->with('lpa/more-info-required', ['lpa-id' => $lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

        $result = $this->controller->payAction();

        $this->assertEquals($response, $result);
    }

    public function testPayActionNoExistingPayment()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);

        $this->lpa->payment->method = null;
        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('setPayment')->with($this->lpa->id, $this->lpa->payment)->andReturn(true)->once();
        $responseUrl = "lpa/{$this->lpa->id}/checkout/pay/response";
        $this->url->shouldReceive('fromRoute')->with('lpa/checkout/pay/response', ['lpa-id' => $this->lpa->id])->andReturn($responseUrl)->once();
        $payment = Mockery::mock(GovPayPayment::class);
        $this->govPayClient->shouldReceive('createPayment')->withArgs(function ($amount, $reference, $description, $returnUrl) {
            /** @var Uri $returnUrl */
            return $amount === (int)($this->lpa->payment->amount * 100)
                && strpos($reference, LpaIdHelper::padLpaId($this->lpa->id) . '-') === 0
                && $description === "Health and welfare LPA for {$this->lpa->document->donor->name}"
                && $returnUrl->getPath() === "/{$this->lpa->id}/checkout/pay/response";
        })->andReturn($payment)->once();
        $payment->payment_id = 'PAYMENT COMPLETE';
        $this->lpaApplicationService->shouldReceive('updatePayment')->with($this->lpa)->andReturn(true)->once();
        $payment->shouldReceive('getPaymentPageUrl')->andReturn($responseUrl)->once();
        $this->redirect->shouldReceive('toUrl')->withArgs([$responseUrl])->andReturn($response)->once();

        $result = $this->controller->payAction();

        $this->assertEquals($response, $result);
    }
}