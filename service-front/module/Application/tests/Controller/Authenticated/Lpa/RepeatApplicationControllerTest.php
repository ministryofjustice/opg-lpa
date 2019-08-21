<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\RepeatApplicationController;
use Application\Form\Lpa\RepeatApplicationForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class RepeatApplicationControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|RepeatApplicationForm
     */
    private $form;
    private $postDataNoRepeat = [
        'isRepeatApplication' => 'no-repeat'
    ];
    private $postDataRepeat = [
        'isRepeatApplication' => 'is-repeat',
        'repeatCaseNumber' => '12345'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->form = Mockery::mock(RepeatApplicationForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\RepeatApplicationForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGetNotRepeatApplication()
    {
        unset($this->lpa->metadata[Lpa::REPEAT_APPLICATION_CONFIRMED]);

        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionGet()
    {
        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([[
            'isRepeatApplication' => 'is-new',
            'repeatCaseNumber'    => null,
        ]])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostNoRepeatInvalid()
    {
        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostInvalid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['isRepeatApplication'])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostRepeatInvalid()
    {
        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostInvalid($this->form, $this->postDataRepeat);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set repeat case number for id: 91333263035
     */
    public function testIndexActionPostNoRepeatFailed()
    {
        $this->lpa->repeatCaseNumber = 12345;

        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostValid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['isRepeatApplication'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoRepeat)->once();
        $this->lpaApplicationService->shouldReceive('deleteRepeatCaseNumber')
            ->withArgs([$this->lpa])->andReturn(false)->once();

        $controller->indexAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set repeat case number for id: 91333263035
     */
    public function testIndexActionPostRepeatFailed()
    {
        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostValid($this->form, $this->postDataRepeat);
        $this->form->shouldReceive('getData')->andReturn($this->postDataRepeat)->once();
        $this->lpaApplicationService->shouldReceive('setRepeatCaseNumber')
            ->withArgs([$this->lpa, $this->postDataRepeat['repeatCaseNumber']])->andReturn(false)->once();

        $controller->indexAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set payment details for id: 91333263035 in RepeatApplicationController
     */
    public function testIndexActionPostRepeatSetPaymentFailed()
    {
        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostValid($this->form, $this->postDataRepeat);
        $this->form->shouldReceive('getData')->andReturn($this->postDataRepeat)->once();
        $this->lpaApplicationService->shouldReceive('setRepeatCaseNumber')
            ->withArgs([$this->lpa, $this->postDataRepeat['repeatCaseNumber']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment) {
                return $lpa->id === $this->lpa->id
                    && $payment->amount === 41.0;
            })->andReturn(false)->once();

        $controller->indexAction();
    }

    public function testIndexActionPostNoRepeatSuccess()
    {
        $this->lpa->repeatCaseNumber = 12345;

        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $response = new Response();

        $this->setPostValid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['isRepeatApplication'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoRepeat)->once();
        $this->lpaApplicationService->shouldReceive('deleteRepeatCaseNumber')
            ->withArgs([$this->lpa])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment) {
                return $lpa->id === $this->lpa->id
                    && $payment->amount === 82.0;
            })->andReturn(true)->once();
        $this->metadata->shouldReceive('setRepeatApplicationConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/fee-reduction');
        $this->setRedirectToRoute('lpa/checkout', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
