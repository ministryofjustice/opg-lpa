<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PeopleToNotifyController;
use Application\Form\Lpa\BlankMainFlowForm;
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
     * @var MockInterface|BlankMainFlowForm
     */
    private $blankMainFlowForm;
    /**
     * @var MockInterface|PeopleToNotifyForm
     */
    private $peopleToNotifyForm;
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

        $this->lpa = FixturesData::getHwLpa();
        $this->lpa->seed = null;

        $this->blankMainFlowForm = Mockery::mock(BlankMainFlowForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa])->andReturn($this->blankMainFlowForm);

        $this->peopleToNotifyForm = Mockery::mock(PeopleToNotifyForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\PeopleToNotifyForm')->andReturn($this->peopleToNotifyForm);
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
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
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
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
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
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($expectedPeopleToNotifyParams, $result->getVariable('peopleToNotify'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->lpa->document->peopleToNotify = [];
        $this->controller->setLpa($this->lpa);
        $this->setPostInvalid($this->blankMainFlowForm, []);
        $this->setMatchedRouteName($this->controller, 'lpa/people-to-notify');
        $this->url->shouldReceive('fromRoute')
            ->with('lpa/people-to-notify/add', ['lpa-id' => $this->lpa->id])
            ->andReturn('lpa/people-to-notify/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals([], $result->getVariable('peopleToNotify'));
    }

    public function testIndexActionPostUpdateMetadata()
    {
        $response = new Response();

        $this->lpa->document->peopleToNotify = [];
        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->blankMainFlowForm, []);
        $this->metadata->shouldReceive('setPeopleToNotifyConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/people-to-notify');
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/instructions', ['lpa-id' => $this->lpa->id], ['fragment' => 'current']])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetReuseDetails()
    {
        $response = new Response();

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/certificate-provider/add', $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetFivePeopleToNotify()
    {
        while (count($this->lpa->document->peopleToNotify) < 5) {
            $this->lpa->document->peopleToNotify[] = FixturesData::getNotifiedPerson();
        }

        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/people-to-notify', ['lpa-id' => $this->lpa->id], ['fragment' => 'current']])->andReturn($response)->once();

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add');
        $this->peopleToNotifyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/people-to-notify/form.twig', $result->getTemplate());
        $this->assertEquals($this->peopleToNotifyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
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