<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\TypeController;
use Application\Form\Lpa\TypeForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class TypeControllerTest extends AbstractControllerTest
{
    /**
     * @var TypeController
     */
    private $controller;
    /**
     * @var MockInterface|TypeForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $postData = [
        'type' => Document::LPA_TYPE_HW
    ];

    public function setUp()
    {
        $this->controller = new TypeController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(TypeForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\TypeForm'])->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->flatten()])->once();
        $this->setMatchedRouteName($this->controller, 'lpa/form-type');
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/donor?lpa-id=' .$this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id]])
            ->andReturn('user/dashboard/create-lpa?lpa-id=' .$this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('user/dashboard/create-lpa?lpa-id=' .$this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals('lpa/donor?lpa-id=' .$this->lpa->id, $result->getVariable('nextUrl'));
        $this->assertEquals('', $result->getVariable('isChangeAllowed'));
        $this->assertEquals([], $result->getVariable('analyticsDimensions'));
    }

    public function testIndexActionGetNoType()
    {
        $this->lpa = new Lpa();
        $this->lpa->id = 123;
        $this->lpa->document = new Document();

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->flatten()])->once();
        $this->setMatchedRouteName($this->controller, 'lpa/form-type');
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/donor?lpa-id=' .$this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id]])
            ->andReturn('user/dashboard/create-lpa?lpa-id=' .$this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('user/dashboard/create-lpa?lpa-id=' .$this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals('lpa/donor?lpa-id=' .$this->lpa->id, $result->getVariable('nextUrl'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
        $this->assertEquals([
            'dimension2' => date('Y-m-d'),
            'dimension3' => 0
        ], $result->getVariable('analyticsDimensions'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->controller->setLpa($this->lpa);
        $this->setPostInvalid($this->form);
        $this->setMatchedRouteName($this->controller, 'lpa/form-type');
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/donor?lpa-id=' .$this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id]])
            ->andReturn('user/dashboard/create-lpa?lpa-id=' .$this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('user/dashboard/create-lpa?lpa-id=' .$this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals('lpa/donor?lpa-id=' .$this->lpa->id, $result->getVariable('nextUrl'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
        $this->assertEquals([], $result->getVariable('analyticsDimensions'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set LPA type for id: 91333263035
     */
    public function testIndexActionPostFailed()
    {
        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')
            ->withArgs([$this->lpa->id, $this->postData['type']])->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')
            ->withArgs([$this->lpa->id, $this->postData['type']])->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/form-type');
        $this->setRedirectToRoute('lpa/donor', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
