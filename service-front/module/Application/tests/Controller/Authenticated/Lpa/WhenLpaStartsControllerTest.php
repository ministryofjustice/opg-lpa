<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhenLpaStartsController;
use Application\Form\Lpa\WhenLpaStartsForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class WhenLpaStartsControllerTest extends AbstractControllerTestCase
{
    /**
     * @var MockInterface|WhenLpaStartsForm
     */
    private $form;
    private $postData = [
        'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(WhenLpaStartsForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\WhenLpaStartsForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        /** @var WhenLpaStartsController $controller */
        $controller = $this->getController(WhenLpaStartsController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')
            ->withArgs([$this->lpa->document->primaryAttorneyDecisions->flatten()])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
    {
        /** @var WhenLpaStartsController $controller */
        $controller = $this->getController(WhenLpaStartsController::class);

        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostFailed()
    {
        /** @var WhenLpaStartsController $controller */
        $controller = $this->getController(WhenLpaStartsController::class);

        $this->lpa->document->primaryAttorneyDecisions = null;
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorneyDecisions->when === $this->postData['when'];
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set when LPA starts for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        /** @var WhenLpaStartsController $controller */
        $controller = $this->getController(WhenLpaStartsController::class);

        $response = new Response();

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorneyDecisions->when === $this->postData['when'];
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/when-lpa-starts');
        $this->setRedirectToRoute('lpa/primary-attorney', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
