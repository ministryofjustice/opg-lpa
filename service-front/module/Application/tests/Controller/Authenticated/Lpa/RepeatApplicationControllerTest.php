<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\RepeatApplicationController;
use Application\Form\Lpa\RepeatApplicationForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Lpa;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class RepeatApplicationControllerTest extends AbstractControllerTestCase
{
    private MockInterface|RepeatApplicationForm $form;
    private array $postDataNoRepeat = [
        'isRepeatApplication' => 'no-repeat'
    ];
    private array $postDataRepeat = [
        'isRepeatApplication' => 'is-repeat',
        'repeatCaseNumber' => '12345'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(RepeatApplicationForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\RepeatApplicationForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGetNotRepeatApplication(): void
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

    public function testIndexActionGet(): void
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

    public function testIndexActionPostNoRepeatInvalid(): void
    {
        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostInvalid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['isRepeatApplication']])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostRepeatInvalid(): void
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

    public function testIndexActionPostNoRepeatFailed(): void
    {
        $this->lpa->repeatCaseNumber = 12345;

        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostValid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['isRepeatApplication']])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoRepeat)->once();
        $this->lpaApplicationService->shouldReceive('deleteRepeatCaseNumber')
            ->withArgs([$this->lpa])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set repeat case number for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostRepeatFailed(): void
    {
        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostValid($this->form, $this->postDataRepeat);
        $this->form->shouldReceive('getData')->andReturn($this->postDataRepeat)->once();
        $this->lpaApplicationService->shouldReceive('setRepeatCaseNumber')
            ->withArgs([$this->lpa, $this->postDataRepeat['repeatCaseNumber']])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set repeat case number for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostRepeatSetPaymentFailed(): void
    {
        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostValid($this->form, $this->postDataRepeat);
        $this->form->shouldReceive('getData')->andReturn($this->postDataRepeat)->once();
        $this->lpaApplicationService->shouldReceive('setRepeatCaseNumber')
            ->withArgs([$this->lpa, $this->postDataRepeat['repeatCaseNumber']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment): bool {
                return $lpa->id === $this->lpa->id
                    && $payment->amount === 46;
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set payment details for id: 91333263035 in RepeatApplicationController');

        $controller->indexAction();
    }

    public function testIndexActionPostNoRepeatSuccess(): void
    {
        $this->lpa->repeatCaseNumber = 12345;

        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);

        $this->setPostValid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['isRepeatApplication']])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoRepeat)->once();
        $this->lpaApplicationService->shouldReceive('deleteRepeatCaseNumber')
            ->withArgs([$this->lpa])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment): bool {
                return $lpa->id === $this->lpa->id
                    && $payment->amount === 92;
            })->andReturn(true)->once();
        $this->metadata->shouldReceive('setRepeatApplicationConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/fee-reduction');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/checkout', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionPostNoRepeatSuccessAfterGoLiveUsesBaseAfter()
    {
        $this->lpa->repeatCaseNumber = 12345;

        /** @var RepeatApplicationController $controller */
        $controller = $this->getController(RepeatApplicationController::class);


        $this->postDataNoRepeat = ['isRepeatApplication' => 'is-new'];
        $this->setPostValid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['isRepeatApplication']])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoRepeat)->once();

        $this->lpaApplicationService->shouldReceive('deleteRepeatCaseNumber')
            ->withArgs([$this->lpa])->andReturn(true)->once();

        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment) {
                $expected = Calculator::getFullFee();
                return $lpa->id === $this->lpa->id
                    && (int)$payment->amount === $expected;
            })
            ->andReturn(true)
            ->once();

        $this->metadata->shouldReceive('setRepeatApplicationConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/fee-reduction');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/checkout', $result->getHeaders()->get('Location')->getUri());
    }
}
