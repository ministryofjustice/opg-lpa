<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PeopleToNotifyController;
use Application\Form\Lpa\PeopleToNotifyForm;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
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

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->form = Mockery::mock(PeopleToNotifyForm::class);
        $this->lpa = FixturesData::getHwLpa();
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
        $this->assertGreaterThan(0, count($this->lpa->document->peopleToNotify));

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($this->controller, 'lpa/people-to-notify');

        $expectedPeopleToNotifyParams = $this->getExpectedPeopleToNotifyParams();

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

    public function testIndexActionGetFivePeopleToNotify()
    {
        while (count($this->lpa->document->peopleToNotify) < 5) {
            $this->lpa->document->peopleToNotify[] = FixturesData::getNotifiedPerson();
        }

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($this->controller, 'lpa/people-to-notify');

        $expectedPeopleToNotifyParams = $this->getExpectedPeopleToNotifyParams();

        $this->url->shouldReceive('fromRoute')
            ->with('lpa/people-to-notify/add', ['lpa-id' => $this->lpa->id])
            ->andReturn('lpa/people-to-notify/add?lpa-id=' . $this->lpa->id)->never();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals($expectedPeopleToNotifyParams, $result->getVariable('peopleToNotify'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->lpa->document->peopleToNotify = [];
        $this->controller->setLpa($this->lpa);
        $this->setPostInvalid($this->form, []);
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

    public function testIndexActionPostUpdateMetadata()
    {
        $response = new Response();

        $this->lpa->document->peopleToNotify = [];
        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, []);
        $this->metadata->shouldReceive('setPeopleToNotifyConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/people-to-notify');
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/instructions', ['lpa-id' => $this->lpa->id], ['fragment' => 'current']])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @return array
     */
    private function getExpectedPeopleToNotifyParams()
    {
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
                'notifiedPerson' => [
                    'name' => $peopleToNotify->name,
                    'address' => $peopleToNotify->address
                ],
                'editRoute' => 'lpa/people-to-notify/edit?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
                'confirmDeleteRoute' => 'lpa/people-to-notify/confirm-delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
                'deleteRoute' => 'lpa/people-to-notify/delete?lpa-id=' . $this->lpa->id . '&idx=' . $idx,
            ];
        }
        return $expectedPeopleToNotifyParams;
    }
}