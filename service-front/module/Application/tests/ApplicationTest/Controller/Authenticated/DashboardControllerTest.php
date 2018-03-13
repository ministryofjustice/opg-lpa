<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\ApplicationList;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\Session\Container;
use Zend\Stdlib\ArrayObject;
use Zend\View\Model\ViewModel;

class DashboardControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableDashboardController
     */
    private $controller;
    /**
     * @var MockInterface|ApplicationList
     */
    private $applicationList;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(TestableDashboardController::class);

        $this->applicationList = Mockery::mock(ApplicationList::class);
        $this->controller->setApplicationList($this->applicationList);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());
    }

    public function testIndexActionZeroLpas()
    {
        $response = new Response();
        $lpasSummary = [
            'applications' => [],
            'total' => 0
        ];

        $this->params->shouldReceive('fromQuery')->withArgs(['search', null])->andReturn(null)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['page', 1])->andReturn(1)->once();
        $this->applicationList->shouldReceive('getLpaSummaries')
            ->withArgs([null, 1, 50])->andReturn($lpasSummary)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['lpa-id'])->andReturn(null)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa-type-no-id'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexAction()
    {
        $lpasSummary = [
            'applications' => [FixturesData::getHwLpa()->abbreviatedToArray()],
            'total' => 1
        ];

        $this->controller->setUser($this->userIdentity);
        $this->params->shouldReceive('fromQuery')->withArgs(['search', null])->andReturn(null)->once();
        //Specify an invalid page number to exercise line 63
        $this->params->shouldReceive('fromRoute')->withArgs(['page', 1])->andReturn(10)->once();
        $this->applicationList->shouldReceive('getLpaSummaries')
            ->withArgs([null, 10, 50])->andReturn($lpasSummary)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($lpasSummary['applications'], $result->getVariable('lpas'));
        $this->assertEquals($lpasSummary['total'], $result->getVariable('lpaTotalCount'));
        $this->assertEquals([
            'page' => 1,
            'pageCount' => 1,
            'pagesInRange' => [1],
            'firstItemNumber' => 1,
            'lastItemNumber' => 1,
            'totalItemCount' => 1
        ], $result->getVariable('paginationControlData'));
        $this->assertEquals(null, $result->getVariable('freeText'));
        $this->assertEquals(false, $result->getVariable('isSearch'));
        $this->assertEquals(['lastLogin' => $this->userIdentity->lastLogin()], $result->getVariable('user'));
    }

    public function testIndexActionMultiplePages()
    {
        $lpasSummary = [
            'applications' => [],
            'total' => 0
        ];

        $lpa = FixturesData::getHwLpa();
        for ($i = 1; $i <= 250; $i++) {
            $lpasSummary['applications'][] = $lpa->abbreviatedToArray();
            $lpasSummary['total'] = $i;
        }

        $this->controller->setUser($this->userIdentity);
        $this->params->shouldReceive('fromQuery')->withArgs(['search', null])->andReturn(null)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['page', 1])->andReturn(2)->once();
        $this->applicationList->shouldReceive('getLpaSummaries')
            ->withArgs([null, 2, 50])->andReturn($lpasSummary)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($lpasSummary['applications'], $result->getVariable('lpas'));
        $this->assertEquals($lpasSummary['total'], $result->getVariable('lpaTotalCount'));
        $this->assertEquals([
            'page' => 2,
            'pageCount' => 5,
            'pagesInRange' => [2, 3, 1, 4, 5],
            'firstItemNumber' => 51,
            'lastItemNumber' => 100,
            'totalItemCount' => 250
        ], $result->getVariable('paginationControlData'));
        $this->assertEquals(null, $result->getVariable('freeText'));
        $this->assertEquals(false, $result->getVariable('isSearch'));
        $this->assertEquals(['lastLogin' => $this->userIdentity->lastLogin()], $result->getVariable('user'));
    }

    public function testIndexActionLastPage()
    {
        $lpasSummary = [
            'applications' => [],
            'total' => 0
        ];

        $lpa = FixturesData::getHwLpa();
        for ($i = 1; $i <= 150; $i++) {
            $lpasSummary['applications'][] = $lpa->abbreviatedToArray();
            $lpasSummary['total'] = $i;
        }

        $this->controller->setUser($this->userIdentity);
        $this->params->shouldReceive('fromQuery')->withArgs(['search', null])->andReturn(null)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['page', 1])->andReturn(3)->once();
        $this->applicationList->shouldReceive('getLpaSummaries')
            ->withArgs([null, 3, 50])->andReturn($lpasSummary)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($lpasSummary['applications'], $result->getVariable('lpas'));
        $this->assertEquals($lpasSummary['total'], $result->getVariable('lpaTotalCount'));
        $this->assertEquals([
            'page' => 3,
            'pageCount' => 3,
            'pagesInRange' => [3, 2, 1],
            'firstItemNumber' => 101,
            'lastItemNumber' => 150,
            'totalItemCount' => 150
        ], $result->getVariable('paginationControlData'));
        $this->assertEquals(null, $result->getVariable('freeText'));
        $this->assertEquals(false, $result->getVariable('isSearch'));
        $this->assertEquals(['lastLogin' => $this->userIdentity->lastLogin()], $result->getVariable('user'));
    }

    public function testCreateActionSeedLpaFailed()
    {
        $response = new Response();

        $this->params->shouldReceive('fromRoute')->withArgs(['lpa-id'])->andReturn(1)->once();
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn(null)->once();
        $this->flashMessenger->shouldReceive('addErrorMessage')
            ->withArgs(['Error creating a new LPA. Please try again.'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $this->controller->createAction();

        $this->assertEquals($response, $result);
    }

    public function testCreateActionSeedLpaPartialSuccess()
    {
        $response = new Response();

        $this->params->shouldReceive('fromRoute')->withArgs(['lpa-id'])->andReturn(1)->once();
        $lpa = FixturesData::getPfLpa();
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn($lpa)->once();
        $this->flashMessenger->shouldReceive('addWarningMessage')
            ->withArgs(['LPA created but could not set seed'])->once();
        $this->lpaApplicationService->shouldReceive('setSeed')->withArgs([$lpa->id, 1])->andReturn(false)->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/form-type', ['lpa-id' => $lpa->id]])->andReturn($response)->once();

        $result = $this->controller->createAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to delete LPA for id: 1
     */
    public function testDeleteActionException()
    {
        $routeMatch = $this->getRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['lpa-id'])->andReturn(1)->once();
        $this->lpaApplicationService->shouldReceive('deleteApplication')->andReturn(false)->once();

        $this->controller->deleteLpaAction();
    }

    public function testDeleteActionSuccess()
    {
        $response = new Response();

        $event = new MvcEvent();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->setRouteMatch($routeMatch);
        $this->controller->setEvent($event);
        $routeMatch->shouldReceive('getParam')->withArgs(['lpa-id'])->andReturn(1)->once();
        $this->lpaApplicationService->shouldReceive('deleteApplication')->andReturn(true)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $this->controller->deleteLpaAction();

        $this->assertEquals($response, $result);
    }

    public function testConfirmDeleteLpaActionNonJs()
    {
        $routeMatch = $this->getRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['lpa-id'])->andReturn(1)->once();
        $lpa = FixturesData::getPfLpa();
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([1])->andReturn($lpa)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteLpaAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/dashboard/confirm-delete.twig', $result->getTemplate());
        $this->assertEquals($lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals($lpa->document->donor->name, $result->getVariable('donorName'));
    }

    public function testConfirmDeleteLpaActionJs()
    {
        $event = new MvcEvent();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->setRouteMatch($routeMatch);
        $this->controller->setEvent($event);
        $routeMatch->shouldReceive('getParam')->withArgs(['lpa-id'])->andReturn(1)->once();
        $lpa = FixturesData::getPfLpa();
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([1])->andReturn($lpa)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteLpaAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/dashboard/confirm-delete.twig', $result->getTemplate());
        $this->assertEquals($lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals($lpa->document->donor->name, $result->getVariable('donorName'));
    }

    public function testTermsAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->termsAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testCheckAuthenticated()
    {
        $response = new Response();

        $this->sessionManager->shouldReceive('start')->never();
        $preAuthRequest = new ArrayObject(['url' => 'https://localhost/user/about-you']);
        $this->request->shouldReceive('getUri')->never();

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', [ 'state'=>'timeout' ]])->andReturn($response)->once();

        Container::setDefaultManager($this->sessionManager);
        $result = $this->controller->testCheckAuthenticated(true);
        Container::setDefaultManager(null);

        $this->assertEquals($response, $result);
    }
}
