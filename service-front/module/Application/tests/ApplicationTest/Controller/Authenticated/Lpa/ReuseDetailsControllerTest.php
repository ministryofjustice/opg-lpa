<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReuseDetailsController;
use Application\Form\Lpa\ReuseDetailsForm;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Mvc\Router\RouteMatch;
use Zend\View\Model\ViewModel;

class ReuseDetailsControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableReuseDetailsController
     */
    private $controller;
    /**
     * @var MockInterface|ReuseDetailsForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $postData = [
        'reuse-details' => 1
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(TestableReuseDetailsController::class);
        $this->controller->setRouter($this->router);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->form = Mockery::mock(ReuseDetailsForm::class);
        $this->lpa = FixturesData::getPfLpa();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Required data missing when attempting to load the reuse details screen
     */
    public function testIndexActionRequiredDataMissing()
    {
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromQuery')->once();

        $this->controller->indexAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Required data missing when attempting to load the reuse details screen
     */
    public function testIndexActionGetMissingParameters()
    {
        $queryParameters = [
            'calling-url' => '',
            'include-trusts' => null,
            'actor-name' => '',
        ];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/donor/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();
        $this->userDetailsSession->user = $this->user;

        $this->formElementManager->shouldReceive('get')->withArgs([
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $this->controller->testGetActorReuseDetails(false, false)]
        ])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters]])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')
            ->withArgs(['action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('/lpa/' . $this->lpa->id . '/donor', $result->cancelUrl);
        $this->assertEquals($queryParameters['actor-name'], $result->actorName);
    }

    public function testIndexActionPostInvalid()
    {
        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/donor/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();
        $this->userDetailsSession->user = $this->user;

        $this->formElementManager->shouldReceive('get')->withArgs([
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $this->controller->testGetActorReuseDetails(false, false)]
        ])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters]])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')
            ->withArgs(['action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id])->once();
        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('/lpa/' . $this->lpa->id . '/donor', $result->cancelUrl);
        $this->assertEquals($queryParameters['actor-name'], $result->actorName);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Calling controller or action could not be determined for processing reuse details request
     */
    public function testIndexActionPostInvalidRouteMatch()
    {
        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/donor/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();
        $this->userDetailsSession->user = $this->user;

        $this->formElementManager->shouldReceive('get')->withArgs([
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $this->controller->testGetActorReuseDetails(false, false)]
        ])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters]])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')
            ->withArgs(['action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData);
        $this->router->shouldReceive('match')->andReturn(null)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('/lpa/' . $this->lpa->id . '/donor', $result->cancelUrl);
        $this->assertEquals($queryParameters['actor-name'], $result->actorName);
    }

    public function testIndexActionPostPrimaryAttorneyAdd()
    {
        $response = new Response();

        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/primary-attorney/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();
        $this->userDetailsSession->user = $this->user;

        $this->formElementManager->shouldReceive('get')->withArgs([
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $this->controller->testGetActorReuseDetails(false, false)]
        ])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters]])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')
            ->withArgs(['action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData);
        $routeMatch = Mockery::mock(RouteMatch::class);
        $this->router->shouldReceive('match')->andReturn($routeMatch)->once();
        $routeMatch->shouldReceive('getParam')
            ->withArgs(['controller'])->andReturn('Authenticated\Lpa\PrimaryAttorneyController')->once();
        $routeMatch->shouldReceive('getParam')->withArgs(['action'])->andReturn('add')->once();
        $this->forward->shouldReceive('dispatch')->withArgs(['Authenticated\Lpa\PrimaryAttorneyController', [
            'action'            => 'add',
            'reuseDetailsIndex' => 1,
            'callingUrl'        => '/lpa/' . $this->lpa->id . '/primary-attorney/add',
        ]])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
