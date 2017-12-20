<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\InstructionsController;
use Application\Form\Lpa\InstructionsAndPreferencesForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class InstructionsControllerTest extends AbstractControllerTest
{
    /**
     * @var InstructionsController
     */
    private $controller;
    /**
     * @var MockInterface|InstructionsAndPreferencesForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $postData = [
        'instruction' => 'Unit test instructions',
        'preference' => 'Unit test preferences'
    ];

    public function setUp()
    {
        $this->controller = new InstructionsController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(InstructionsAndPreferencesForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\InstructionsAndPreferencesForm', ['lpa' => $this->lpa]])
            ->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->flatten()])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('isPfLpa'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->controller->setLpa($this->lpa);
        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('isPfLpa'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set LPA instructions for id: 91333263035
     */
    public function testIndexActionPostInstructionsFailed()
    {
        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa->id, $this->postData['instruction']])->andReturn(false)->once();

         $this->controller->indexAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set LPA preferences for id: 91333263035
     */
    public function testIndexActionPostPreferencesFailed()
    {
        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa->id, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa->id, $this->postData['preference']])->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa->id, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa->id, $this->postData['preference']])->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/instructions');
        $this->setRedirectToRoute('lpa/applicant', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionPostMetadata()
    {
        $response = new Response();

        $this->lpa->metadata['instruction-confirmed'] = false;

        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa->id, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa->id, $this->postData['preference']])->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/instructions');
        $this->setRedirectToRoute('lpa/applicant', $this->lpa, $response);
        $this->metadata->shouldReceive('setInstructionConfirmed')
            ->withArgs([$this->lpa])->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
