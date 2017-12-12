<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\FeeReductionController;
use Application\Form\Lpa\FeeReductionForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Form\Element\Select;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class FeeReductionControllerTest extends AbstractControllerTest
{
    /**
     * @var FeeReductionController
     */
    private $controller;
    /**
     * @var MockInterface|FeeReductionForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $options;
    /**
     * @var MockInterface|Select
     */
    private $reductionOptions;

    public function setUp()
    {
        $this->controller = new FeeReductionController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(FeeReductionForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\FeeReductionForm', ['lpa' => $this->lpa]])->andReturn($this->form);

        $this->options = [
            'value_options' => [
                'reducedFeeReceivesBenefits' => ['value' => 'reducedFeeReceivesBenefits'],
                'reducedFeeUniversalCredit' => ['value' => 'reducedFeeUniversalCredit'],
                'reducedFeeLowIncome' => ['value' => 'reducedFeeLowIncome'],
                'notApply' => ['value' => 'notApply']
            ]
        ];

        $this->reductionOptions = Mockery::mock(Select::class);
        $this->reductionOptions->shouldReceive('getOptions')->andReturn($this->options);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionGetNoPayment()
    {
        $this->lpa->payment = null;
        $this->controller->setLpa($this->lpa);
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionGetExistingPaymentReducedFeeReceivesBenefits()
    {
        $this->assertNotNull($this->lpa->payment);

        $this->lpa->payment->reducedFeeReceivesBenefits = true;
        $this->lpa->payment->reducedFeeAwardedDamages = true;

        $this->controller->setLpa($this->lpa);
        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'reducedFeeReceivesBenefits']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionGetExistingPaymentReducedFeeUniversalCredit()
    {
        $this->assertNotNull($this->lpa->payment);

        $this->lpa->payment->reducedFeeUniversalCredit = true;

        $this->controller->setLpa($this->lpa);
        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'reducedFeeUniversalCredit']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionGetExistingPaymentReducedFeeLowIncome()
    {
        $this->assertNotNull($this->lpa->payment);

        $this->lpa->payment->reducedFeeLowIncome = true;

        $this->controller->setLpa($this->lpa);
        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'reducedFeeLowIncome']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionGetExistingPaymentReducedFeeNotApply()
    {
        $this->assertNotNull($this->lpa->payment);

        $this->lpa->payment->reducedFeeReceivesBenefits = false;
        $this->lpa->payment->reducedFeeAwardedDamages = false;
        $this->lpa->payment->reducedFeeUniversalCredit = false;
        $this->lpa->payment->reducedFeeLowIncome = false;

        $this->controller->setLpa($this->lpa);
        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'notApply']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionPostInvalid()
    {
        $this->lpa->payment = null;
        $this->controller->setLpa($this->lpa);
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set payment details for id: 91333263035 in FeeReductionController
     */
    public function testIndexActionPostFailed()
    {
        $postData = ['reductionOptions' => 'reducedFeeReceivesBenefits'];

        $this->lpa->payment = null;
        $this->controller->setLpa($this->lpa);
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpaId, $payment) {
                return $lpaId === $this->lpa->id
                    && $payment->reducedFeeReceivesBenefits == true
                    && $payment->reducedFeeAwardedDamages == true
                    && $payment->reducedFeeLowIncome == null
                    && $payment->reducedFeeUniversalCredit === null;
            })->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionSuccessReducedFeeUniversalCredit()
    {
        $response = new Response();
        $postData = ['reductionOptions' => 'reducedFeeUniversalCredit'];

        $this->controller->setLpa($this->lpa);
        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'notApply']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpaId, $payment) {
                return $lpaId === $this->lpa->id
                    && $payment->reducedFeeReceivesBenefits == false
                    && $payment->reducedFeeAwardedDamages == null
                    && $payment->reducedFeeLowIncome == false
                    && $payment->reducedFeeUniversalCredit === true;
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/fee-reduction');
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/checkout', ['lpa-id' => $this->lpa->id], []])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionSuccessReducedFeeLowIncome()
    {
        $response = new Response();
        $postData = ['reductionOptions' => 'reducedFeeLowIncome'];

        $this->controller->setLpa($this->lpa);
        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'notApply']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpaId, $payment) {
                return $lpaId === $this->lpa->id
                    && $payment->reducedFeeReceivesBenefits == false
                    && $payment->reducedFeeAwardedDamages == null
                    && $payment->reducedFeeLowIncome == true
                    && $payment->reducedFeeUniversalCredit === false;
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/fee-reduction');
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/checkout', ['lpa-id' => $this->lpa->id], []])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionSuccessNotApply()
    {
        $response = new Response();
        $postData = ['reductionOptions' => 'notApply'];

        $this->lpa->payment = null;
        $this->controller->setLpa($this->lpa);
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpaId, $payment) {
                return $lpaId === $this->lpa->id
                    && $payment->reducedFeeReceivesBenefits == null
                    && $payment->reducedFeeAwardedDamages == null
                    && $payment->reducedFeeLowIncome == null
                    && $payment->reducedFeeUniversalCredit === null;
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/fee-reduction');
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/checkout', ['lpa-id' => $this->lpa->id], []])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
