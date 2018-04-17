<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\LifeSustainingController;
use Application\Form\Lpa\LifeSustainingForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class LifeSustainingControllerTest extends AbstractControllerTest
{
    /**
     * @var LifeSustainingController
     */
    private $controller;
    /**
     * @var MockInterface|LifeSustainingForm
     */
    private $form;
    private $postData = [
        'canSustainLife' => true
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(LifeSustainingController::class);

        $this->form = Mockery::mock(LifeSustainingForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\LifeSustainingForm', ['lpa' => $this->lpa]])->andReturn($this->form);
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
     * @expectedExceptionMessage API client failed to set life sustaining for id: 91333263035
     */
    public function testIndexActionPostFailed()
    {
        $this->lpa->document->primaryAttorneyDecisions->canSustainLife = false;

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneyDecisions])->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $this->lpa->document->primaryAttorneyDecisions = null;

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                        && $primaryAttorneyDecisions->canSustainLife === true;
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/life-sustaining');
        $this->setRedirectToRoute('lpa/primary-attorney', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}