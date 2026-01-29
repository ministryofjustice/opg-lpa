<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\InstructionsController;
use Application\Form\Lpa\InstructionsAndPreferencesForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class InstructionsControllerTest extends AbstractControllerTestCase
{
    private MockInterface|InstructionsAndPreferencesForm $form;
    private array $postData = [
        'instruction' => 'Unit test instructions',
        'preference' => 'Unit test preferences'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(InstructionsAndPreferencesForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\InstructionsAndPreferencesForm', ['lpa' => $this->lpa]])
            ->andReturn($this->form);
    }

    public function testIndexActionGet(): void
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

    public function testIndexActionPostInvalid(): void
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

    public function testIndexActionPostInstructionsFailed(): void
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa, $this->postData['instruction']])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set LPA instructions for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostPreferencesFailed(): void
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa, $this->postData['preference']])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set LPA preferences for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess(): void
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa, $this->postData['preference']])->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/instructions');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/applicant', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionPostMetadata(): void
    {
        /** @var InstructionsController $controller */
        $controller = $this->getController(InstructionsController::class);

        $this->lpa->metadata['instruction-confirmed'] = false;

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setInstructions')
            ->withArgs([$this->lpa, $this->postData['instruction']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPreferences')
            ->withArgs([$this->lpa, $this->postData['preference']])->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/instructions');
        $this->metadata->shouldReceive('setInstructionConfirmed')
            ->withArgs([$this->lpa])->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/applicant', $result->getHeaders()->get('Location')->getUri());
    }
}
