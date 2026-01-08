<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\AccordionViewModelListener;
use Application\Model\Service\Session\PersistentSessionDetails;
use Application\Service\AccordionService;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AccordionViewModelListenerTest extends TestCase
{
    /** @var AccordionService&MockObject */
    private AccordionService $accordion;

    /** @var PersistentSessionDetails&MockObject */
    private PersistentSessionDetails $sessionDetails;

    private AccordionViewModelListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accordion = $this->createMock(AccordionService::class);
        $this->sessionDetails = $this->createMock(PersistentSessionDetails::class);

        $this->listener = new AccordionViewModelListener(
            $this->accordion,
            $this->sessionDetails
        );
    }

    public function testOnRenderSetsRouteAndEmptyItemsWhenNoLpa(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $viewModel = new ViewModel();

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/donor');

        $event->method('getViewModel')->willReturn($viewModel);
        $event->method('getRouteMatch')->willReturn($routeMatch);

        $this->sessionDetails->method('getPreviousRoute')->willReturn('user/dashboard');

        $this->accordion->expects($this->never())->method('top');
        $this->accordion->expects($this->never())->method('bottom');

        $this->listener->onRender($event);

        $this->assertSame(
            ['current' => 'lpa/donor', 'previous' => 'user/dashboard'],
            $viewModel->getVariable('route')
        );
        $this->assertSame([], $viewModel->getVariable('accordionTopItems'));
        $this->assertSame([], $viewModel->getVariable('accordionBottomItems'));
    }

    public function testOnRenderSetsRouteAndEmptyItemsWhenNoCurrentRouteName(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $viewModel = new ViewModel();

        // LPA exists, but route name is null -> should default to empty items
        $lpa = $this->createMock(Lpa::class);
        $viewModel->setVariable('lpa', $lpa);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getMatchedRouteName')->willReturn(''); // treated as null

        $event->method('getViewModel')->willReturn($viewModel);
        $event->method('getRouteMatch')->willReturn($routeMatch);

        $this->sessionDetails->method('getPreviousRoute')->willReturn('user/about-you');

        $this->accordion->expects($this->never())->method('top');
        $this->accordion->expects($this->never())->method('bottom');

        $this->listener->onRender($event);

        $this->assertSame(
            ['current' => null, 'previous' => 'user/about-you'],
            $viewModel->getVariable('route')
        );
        $this->assertSame([], $viewModel->getVariable('accordionTopItems'));
        $this->assertSame([], $viewModel->getVariable('accordionBottomItems'));
    }

    public function testOnRenderComputesItemsWhenLpaExistsOnRootViewModel(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $viewModel = new ViewModel();

        $lpa = $this->createMock(Lpa::class);
        $viewModel->setVariable('lpa', $lpa);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/donor');

        $event->method('getViewModel')->willReturn($viewModel);
        $event->method('getRouteMatch')->willReturn($routeMatch);

        $this->sessionDetails->method('getPreviousRoute')->willReturn('lpa/form-type');

        $top = [['routeName' => 'lpa/form-type']];
        $bottom = [['routeName' => 'lpa/when-lpa-starts']];

        $this->accordion->expects($this->once())
            ->method('top')
            ->with($lpa, 'lpa/donor')
            ->willReturn($top);

        $this->accordion->expects($this->once())
            ->method('bottom')
            ->with($lpa, 'lpa/donor')
            ->willReturn($bottom);

        $this->listener->onRender($event);

        $this->assertSame(
            ['current' => 'lpa/donor', 'previous' => 'lpa/form-type'],
            $viewModel->getVariable('route')
        );
        $this->assertSame($top, $viewModel->getVariable('accordionTopItems'));
        $this->assertSame($bottom, $viewModel->getVariable('accordionBottomItems'));
    }

    public function testOnRenderFindsLpaInChildViewModel(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $root = new ViewModel();

        $child = new ViewModel();
        $lpa = $this->createMock(Lpa::class);
        $child->setVariable('lpa', $lpa);
        $root->addChild($child);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/donor');

        $event->method('getViewModel')->willReturn($root);
        $event->method('getRouteMatch')->willReturn($routeMatch);

        $this->sessionDetails->method('getPreviousRoute')->willReturn('user/dashboard');

        $top = [['routeName' => 'lpa/form-type']];
        $bottom = [['routeName' => 'lpa/when-lpa-starts']];

        $this->accordion->expects($this->once())
            ->method('top')
            ->with($lpa, 'lpa/donor')
            ->willReturn($top);

        $this->accordion->expects($this->once())
            ->method('bottom')
            ->with($lpa, 'lpa/donor')
            ->willReturn($bottom);

        $this->listener->onRender($event);

        $this->assertSame(
            ['current' => 'lpa/donor', 'previous' => 'user/dashboard'],
            $root->getVariable('route')
        );
        $this->assertSame($top, $root->getVariable('accordionTopItems'));
        $this->assertSame($bottom, $root->getVariable('accordionBottomItems'));
    }

    public function testOnRenderReturnsEarlyWhenViewModelIsNotAViewModel(): void
    {
        $event = $this->createMock(MvcEvent::class);

        $event->method('getViewModel')->willReturn(new class {
        });

        $this->sessionDetails->expects($this->never())->method('getPreviousRoute');
        $this->accordion->expects($this->never())->method('top');
        $this->accordion->expects($this->never())->method('bottom');

        $this->listener->onRender($event);

        $this->assertTrue(true);
    }
}
