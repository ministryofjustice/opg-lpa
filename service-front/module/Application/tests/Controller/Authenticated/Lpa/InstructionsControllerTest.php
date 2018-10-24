<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\InstructionsController;
use Application\Form\Lpa\InstructionsAndPreferencesForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class InstructionsControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|InstructionsAndPreferencesForm
     */
    private $form;
    private $postData = [
        'instruction' => 'Unit test instructions',
        'preference' => 'Unit test preferences'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->form = Mockery::mock(InstructionsAndPreferencesForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\InstructionsAndPreferencesForm', ['lpa' => $this->lpa]])
            ->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->flatten()])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set LPA instructions for id: 91333263035
     */
    public function testIndexActionPostInstructionsFailed()
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa, $this->postData['instruction']])->andReturn(false)->once();

         $controller->indexAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set LPA preferences for id: 91333263035
     */
    public function testIndexActionPostPreferencesFailed()
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa, $this->postData['preference']])->andReturn(false)->once();

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $response = new Response();

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa, $this->postData['preference']])->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/instructions');
        $this->setRedirectToRoute('lpa/applicant', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionPostMetadata()
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $response = new Response();

        $this->lpa->metadata['instruction-confirmed'] = false;

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa, $this->postData['preference']])->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/instructions');
        $this->setRedirectToRoute('lpa/applicant', $this->lpa, $response);
        $this->metadata->shouldReceive('setInstructionConfirmed')
            ->withArgs([$this->lpa])->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
