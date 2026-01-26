<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\FeeReductionController;
use Application\Form\Lpa\FeeReductionForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Laminas\Form\Element\Select;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class FeeReductionControllerTest extends AbstractControllerTestCase
{
    private MockInterface|FeeReductionForm $form;
    private array $options;
    private MockInterface|Select $reductionOptions;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(FeeReductionForm::class);
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

    public function testIndexActionGetNoPayment(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $this->lpa->payment = null;

        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionGetExistingPaymentReducedFeeReceivesBenefits(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $this->assertNotNull($this->lpa->payment);

        $this->lpa->payment->reducedFeeReceivesBenefits = true;
        $this->lpa->payment->reducedFeeAwardedDamages = true;

        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'reducedFeeReceivesBenefits']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionGetExistingPaymentReducedFeeUniversalCredit(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $this->assertNotNull($this->lpa->payment);

        $this->lpa->payment->reducedFeeUniversalCredit = true;

        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'reducedFeeUniversalCredit']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionGetExistingPaymentReducedFeeLowIncome(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $this->assertNotNull($this->lpa->payment);

        $this->lpa->payment->reducedFeeLowIncome = true;

        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'reducedFeeLowIncome']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionGetExistingPaymentReducedFeeNotApply(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $this->assertNotNull($this->lpa->payment);

        $this->lpa->payment->reducedFeeReceivesBenefits = false;
        $this->lpa->payment->reducedFeeAwardedDamages = false;
        $this->lpa->payment->reducedFeeUniversalCredit = false;
        $this->lpa->payment->reducedFeeLowIncome = false;

        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'notApply']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $this->lpa->payment = null;
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }

    public function testIndexActionPostFailed(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $postData = ['reductionOptions' => 'reducedFeeReceivesBenefits'];

        $this->lpa->payment = null;
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment): bool {
                return $lpa->id === $this->lpa->id
                    && $payment->reducedFeeReceivesBenefits == true
                    && $payment->reducedFeeAwardedDamages == true
                    && $payment->reducedFeeLowIncome == null
                    && $payment->reducedFeeUniversalCredit === null;
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set payment details for id: 91333263035 in FeeReductionController');

        $controller->indexAction();
    }

    public function testIndexActionSuccessReducedFeeUniversalCredit(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $postData = ['reductionOptions' => 'reducedFeeUniversalCredit'];

        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'notApply']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment): bool {
                return $lpa->id === $this->lpa->id
                    && $payment->reducedFeeReceivesBenefits == false
                    && $payment->reducedFeeAwardedDamages == null
                    && $payment->reducedFeeLowIncome == false
                    && $payment->reducedFeeUniversalCredit === true;
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/fee-reduction');
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/checkout', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionSuccessReducedFeeLowIncome(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $postData = ['reductionOptions' => 'reducedFeeLowIncome'];

        $this->form->shouldReceive('bind')->withArgs([['reductionOptions' => 'notApply']])->once();
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment): bool {
                return $lpa->id === $this->lpa->id
                    && $payment->reducedFeeReceivesBenefits == false
                    && $payment->reducedFeeAwardedDamages == null
                    && $payment->reducedFeeLowIncome == true
                    && $payment->reducedFeeUniversalCredit === false;
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/fee-reduction');
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/checkout', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionSuccessNotApply(): void
    {
        /** @var FeeReductionController $controller */
        $controller = $this->getController(FeeReductionController::class);

        $postData = ['reductionOptions' => 'notApply'];

        $this->lpa->payment = null;
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->withArgs(['reductionOptions'])->andReturn($this->reductionOptions)->once();
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment): bool {
                return $lpa->id === $this->lpa->id
                    && $payment->reducedFeeReceivesBenefits == null
                    && $payment->reducedFeeAwardedDamages == null
                    && $payment->reducedFeeLowIncome == null
                    && $payment->reducedFeeUniversalCredit === null;
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/fee-reduction');
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/checkout', $result->getHeaders()->get('Location')->getUri());
    }
}
