<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PeopleToNotifyController;
use Application\Form\Lpa\PeopleToNotifyForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class PeopleToNotifyControllerTest extends AbstractControllerTest
{
    /**
     * @var PeopleToNotifyController
     */
    private $controller;
    /**
     * @var MockInterface|PeopleToNotifyForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new PeopleToNotifyController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(PeopleToNotifyForm::class);
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

    public function testIndexActionGetNoPeopleToNotify()
    {
        $this->lpa->document->peopleToNotify = [];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($this->controller, 'lpa/people-to-notify');
        $this->url->shouldReceive('fromRoute')
            ->with('lpa/people-to-notify/add', ['lpa-id' => $this->lpa->id])
            ->andReturn('lpa/people-to-notify/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals([], $result->getVariable('peopleToNotify'));
    }

    public function testIndexActionGetMultiplePeopleToNotify()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($this->controller, 'lpa/people-to-notify');

        $expectedPeopleToNotifyParams = [];
        foreach ($this->lpa->document->peopleToNotify as $idx => $peopleToNotify) {
            $this->url->shouldReceive('fromRoute')
                ->with('lpa/people-to-notify/edit', ['lpa-id' => $this->lpa->id, 'idx' => $idx])
                ->andReturn('lpa/people-to-notify/edit?lpa-id=' . $this->lpa->id . '&idx=' . $idx)->once();
            $this->url->shouldReceive('fromRoute')
                ->with('lpa/people-to-notify/confirm-delete', ['lpa-id' => $this->lpa->id, 'idx' => $idx])
                ->andReturn('lpa/people-to-notify/confirm-delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx)->once();
            $this->url->shouldReceive('fromRoute')
                ->with('lpa/people-to-notify/delete', ['lpa-id' => $this->lpa->id, 'idx' => $idx])
                ->andReturn('lpa/people-to-notify/delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx)->once();

            $expectedPeopleToNotifyParams[] = [
                'notifiedPerson'    => [
                    'name'          => $peopleToNotify->name,
                    'address'       => $peopleToNotify->address
                ],
                'editRoute'         => 'lpa/people-to-notify/edit?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
                'confirmDeleteRoute'=> 'lpa/people-to-notify/confirm-delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
                'deleteRoute'       => 'lpa/people-to-notify/delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
            ];
        }

        $this->url->shouldReceive('fromRoute')
            ->with('lpa/people-to-notify/add', ['lpa-id' => $this->lpa->id])
            ->andReturn('lpa/people-to-notify/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals($expectedPeopleToNotifyParams, $result->getVariable('peopleToNotify'));
    }
}