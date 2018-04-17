<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhenLpaStartsController;
use Application\Form\Lpa\WhenLpaStartsForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class WhenLpaStartsControllerTest extends AbstractControllerTest
{
    /**
     * @var WhenLpaStartsController
     */
    private $controller;
    /**
     * @var MockInterface|WhenLpaStartsForm
     */
    private $form;
    private $postData = [
        'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(WhenLpaStartsController::class);

        $this->form = Mockery::mock(WhenLpaStartsForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\WhenLpaStartsForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')
            ->withArgs([$this->lpa->document->primaryAttorneyDecisions->flatten()])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set when LPA starts for id: 91333263035
     */
    public function testIndexActionPostFailed()
    {
        $this->lpa->document->primaryAttorneyDecisions = null;
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorneyDecisions->when === $this->postData['when'];
            })->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorneyDecisions->when === $this->postData['when'];
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/when-lpa-starts');
        $this->setRedirectToRoute('lpa/primary-attorney', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
