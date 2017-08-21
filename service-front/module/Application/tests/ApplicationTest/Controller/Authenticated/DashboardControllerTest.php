<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\DashboardController;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\ApplicationList;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\View\Model\ViewModel;

class DashboardControllerTest extends AbstractControllerTest
{
    /**
     * @var DashboardController
     */
    private $controller;
    /**
     * @var MockInterface|ApplicationList
     */
    private $applicationList;

    public function setUp()
    {
        $this->controller = new DashboardController();
        parent::controllerSetUp($this->controller);

        $this->applicationList = Mockery::mock(ApplicationList::class);
        $this->serviceLocator->shouldReceive('get')->with('ApplicationList')->andReturn($this->applicationList);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());
        $this->controller->setUser($this->userIdentity);
    }

    public function testIndexAction()
    {
        $lpasSummary = [
            'applications' => [FixturesData::getHwLpa()->abbreviatedToArray()],
            'total' => 1
        ];

        $this->params->shouldReceive('fromQuery')->with('search', null)->andReturn(null)->once();
        $this->params->shouldReceive('fromRoute')->with('page', 1)->andReturn(1)->once();
        $this->applicationList->shouldReceive('getLpaSummaries')->with(null, 1, 50)->andReturn($lpasSummary)->once();

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
}