<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Service\AccordionService;
use Application\Model\Service\Session\PersistentSessionDetails;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Model\ViewModel;
use MakeShared\DataModel\Lpa\Lpa;

final class AccordionViewModelListener extends AbstractListenerAggregate
{
    public function __construct(
        private AccordionService $accordion,
        private PersistentSessionDetails $sessionDetails,
    ) {
    }

    public function attach(EventManagerInterface $events, $priority = 100): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_RENDER,
            [$this, 'onRender']
        );
    }

    public function onRender(MvcEvent $event): void
    {
        $viewModel = $event->getViewModel();
        if (!$viewModel instanceof ViewModel) {
            return;
        }

        $currentRouteName = $this->getMatchedRouteName($event);
        $previousRouteName = $this->sessionDetails->getPreviousRoute();

        // Provide a stable template contract for both Laminas and Mezzio:
        // route.current, route.previous
        $viewModel->setVariable('route', [
            'current'  => $currentRouteName,
            'previous' => $previousRouteName,
        ]);

        // Find the LPA in the composed view models (layout/child/etc)
        $lpa = $this->findLpa($viewModel);

        // Provide safe defaults to templates
        if (!$lpa instanceof Lpa || empty($currentRouteName)) {
            $viewModel->setVariable('accordionTopItems', []);
            $viewModel->setVariable('accordionBottomItems', []);
            return;
        }

        $viewModel->setVariable('accordionTopItems', $this->accordion->top($lpa, $currentRouteName));
        $viewModel->setVariable('accordionBottomItems', $this->accordion->bottom($lpa, $currentRouteName));
    }

    private function getMatchedRouteName(MvcEvent $event): ?string
    {
        $match = $event->getRouteMatch();

        if ($match instanceof RouteMatch) {
            $name = $match->getMatchedRouteName();
            return is_string($name) && $name !== '' ? $name : null;
        }

        return null;
    }

    private function findLpa(ModelInterface $model): ?Lpa
    {
        $lpa = $model->getVariable('lpa');
        if ($lpa instanceof Lpa) {
            return $lpa;
        }

        foreach ($model->getChildren() as $child) {
            if ($child instanceof ModelInterface) {
                $found = $this->findLpa($child);
                if ($found instanceof Lpa) {
                    return $found;
                }
            }
        }

        return null;
    }
}
