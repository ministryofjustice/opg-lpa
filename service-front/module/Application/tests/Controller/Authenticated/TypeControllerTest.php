<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\TypeController;
use Application\Form\Lpa\TypeForm;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class TypeControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|TypeForm
     */
    private $form;
    private $postData = [
        'type' => 'property-and-financial'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->form = Mockery::mock(TypeForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\TypeForm'])->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/type/index.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
        $this->assertEquals([
            'dimension2' => (new DateTime())->format('Y-m-d'),
            'dimension3' => 0
        ], $result->getVariable('analyticsDimensions'));
    }

    public function testIndexActionPostInvalid()
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/type/index.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
        $this->assertEquals([
            'dimension2' => (new DateTime())->format('Y-m-d'),
            'dimension3' => 0
        ], $result->getVariable('analyticsDimensions'));
    }

    public function testIndexActionPostCreationError()
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $response = new Response();

        $this->setPostValid($this->form, $this->postData);
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn(null)->once();
        $this->flashMessenger->shouldReceive('addErrorMessage')
            ->withArgs(['Error creating a new LPA. Please try again.'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set LPA type for id: 5531003156
     */
    public function testIndexActionPostSetTypeException()
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setPostValid($this->form, $this->postData);
        $lpa = FixturesData::getHwLpa();
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn($lpa)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')
            ->andReturn($lpa->id, $this->postData['type'])->andReturn(false)->once();

        $controller->indexAction();
    }

    public function testIndexAction()
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $response = new Response();

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

        $this->setMatchedRouteName($controller, 'lpa/form-type');
        $this->setRedirectToRoute('lpa/donor', $lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
