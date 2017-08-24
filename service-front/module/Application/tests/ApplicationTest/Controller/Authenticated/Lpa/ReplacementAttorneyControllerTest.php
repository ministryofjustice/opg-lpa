<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReplacementAttorneyController;
use Application\Form\Lpa\AttorneyForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class ReplacementAttorneyControllerTest extends AbstractControllerTest
{
    /**
     * @var ReplacementAttorneyController
     */
    private $controller;
    /**
     * @var MockInterface|AttorneyForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new ReplacementAttorneyController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(AttorneyForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa])->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionGetNoReplacementAttorney()
    {
        $this->lpa->document->replacementAttorneys = [];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($this->controller, 'lpa/replacement-attorney');
        $this->url->shouldReceive('fromRoute')
            ->with('lpa/replacement-attorney/add', ['lpa-id' => $this->lpa->id])
            ->andReturn('lpa/replacement-attorney/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('lpa/replacement-attorney/add?lpa-id=' . $this->lpa->id, $result->getVariable('addRoute'));
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals([], $result->getVariable('attorneys'));
    }

    public function testIndexActionGetMultipleReplacementAttorney()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($this->controller, 'lpa/replacement-attorney');

        $expectedAttorneyParams = [];
        foreach ($this->lpa->document->replacementAttorneys as $idx => $attorney) {
            $this->url->shouldReceive('fromRoute')
                ->with('lpa/replacement-attorney/edit', ['lpa-id' => $this->lpa->id, 'idx' => $idx])
                ->andReturn('lpa/replacement-attorney/edit?lpa-id=' . $this->lpa->id . '&idx=' . $idx)->once();
            $this->url->shouldReceive('fromRoute')
                ->with('lpa/replacement-attorney/confirm-delete', ['lpa-id' => $this->lpa->id, 'idx' => $idx])
                ->andReturn('lpa/replacement-attorney/confirm-delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx)->once();
            $this->url->shouldReceive('fromRoute')
                ->with('lpa/replacement-attorney/delete', ['lpa-id' => $this->lpa->id, 'idx' => $idx])
                ->andReturn('lpa/replacement-attorney/delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx)->once();

            $expectedAttorneyParams[] = [
                'attorney'          => [
                    'address'       => $attorney->address,
                    'name'          => $attorney->name
                ],
                'editRoute'         => 'lpa/replacement-attorney/edit?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
                'confirmDeleteRoute'=> 'lpa/replacement-attorney/confirm-delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
                'deleteRoute'       => 'lpa/replacement-attorney/delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
            ];
        }
        
        $this->url->shouldReceive('fromRoute')
            ->with('lpa/replacement-attorney/add', ['lpa-id' => $this->lpa->id])
            ->andReturn('lpa/replacement-attorney/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('lpa/replacement-attorney/add?lpa-id=' . $this->lpa->id, $result->getVariable('addRoute'));
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals($expectedAttorneyParams, $result->getVariable('attorneys'));
    }
}