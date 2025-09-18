<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\LifeSustainingController;
use Application\Form\Lpa\LifeSustainingForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class LifeSustainingControllerTest extends AbstractControllerTestCase
{
    /**
     * @var MockInterface|LifeSustainingForm
     */
    private $form;
    private $postData = [
        'canSustainLife' => true
    ];

    public function setUp() : void
    {
        parent::setUp();

        $this->form = Mockery::mock(LifeSustainingForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\LifeSustainingForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        /** @var LifeSustainingController $controller */
        $controller = $this->getController(LifeSustainingController::class);

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
        /** @var LifeSustainingController $controller */
        $controller = $this->getController(LifeSustainingController::class);

        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostFailed()
    {
        /** @var LifeSustainingController $controller */
        $controller = $this->getController(LifeSustainingController::class);

        $this->lpa->document->primaryAttorneyDecisions->canSustainLife = false;

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneyDecisions])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set life sustaining for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        /** @var LifeSustainingController $controller */
        $controller = $this->getController(LifeSustainingController::class);

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
        $this->setMatchedRouteNameHttp($controller, 'lpa/life-sustaining');
        $this->setRedirectToRoute('lpa/primary-attorney', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
