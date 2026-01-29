<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class LpaViewInjectListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_RENDER,
            [$this, 'injectLpaIntoView'],
            $priority
        );
    }

    public function injectLpaIntoView(MvcEvent $event): void
    {
        $lpa = $event->getParam(LpaLoaderListener::ATTR_LPA);

        if ($lpa === null) {
            return;
        }

        $currentRoute = $event->getParam(LpaLoaderListener::ATTR_CURRENT_ROUTE);

        $viewModel = $event->getViewModel();

        if ($viewModel instanceof ViewModel) {
            $viewModel->setVariable('lpa', $lpa);
            $viewModel->setVariable('currentRouteName', $currentRoute);

            foreach ($viewModel->getChildren() as $child) {
                if ($child instanceof ViewModel && !$child instanceof JsonModel) {
                    $child->setVariable('lpa', $lpa);
                    $child->setVariable('currentRouteName', $currentRoute);
                }
            }
        }
    }
}
