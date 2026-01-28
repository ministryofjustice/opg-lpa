<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\TypeController;
use Application\Form\Lpa\TypeForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

final class TypeControllerTest extends AbstractControllerTestCase
{
    private MockInterface|TypeForm $form;
    private array $postData = [
        'type' => 'property-and-financial'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(TypeForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\TypeForm'])->andReturn($this->form);
    }

    public function testIndexActionGet(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setMatchedRouteName($controller, 'lpa/form-type');

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/type/index.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setMatchedRouteName($controller, 'lpa/form-type');

        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/type/index.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
    }

    public function testIndexActionPostCreationError(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setMatchedRouteName($controller, 'lpa/form-type');

        $this->setPostValid($this->form, $this->postData);
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn(null)->once();
        $this->flashMessenger->shouldReceive('addErrorMessage')
            ->withArgs(['Error creating a new LPA. Please try again.'])->once();
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/user/dashboard', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionPostSetTypeException(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setMatchedRouteName($controller, 'lpa/form-type');

        $this->setPostValid($this->form, $this->postData);
        $lpa = FixturesData::getHwLpa();
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn($lpa)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')
            ->andReturn($lpa->id, $this->postData['type'])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set LPA type for id: 5531003156');

        $controller->indexAction();
    }

    public function testIndexAction(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setPostValid($this->form, $this->postData);
        $lpa = new Lpa([
            'id' => 123,
            'document' => [
                'type' => $this->postData['type'],
            ]
        ]);
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn($lpa)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')
            ->andReturn($lpa->getId(), $this->postData['type'])->andReturn(true)->once();

        $routeMatch = $this->getRouteMatch($controller);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn('lpa/form-type')->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/123/donor', $result->getHeaders()->get('Location')->getUri());
    }
}
