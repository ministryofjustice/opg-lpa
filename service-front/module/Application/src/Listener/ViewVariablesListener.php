<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\Service\Date\DateService;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

class ViewVariablesListener extends AbstractListenerAggregate implements LoggerAwareInterface
{
    use LoggerTrait;

    public function __construct(
        protected DateService $dateService,
    ) {
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_RENDER,
            [$this, 'listen'],
            $priority
        );
    }

    // This listener will be removed when moving to Mezzio, view params will be set in
    // CommonTemplateVariablesTrait instead
    public function listen(MvcEvent $event): null
    {
        $result = $event->getResult();

        if (!$result instanceof ViewModel || $result instanceof JsonModel) {
            return null;
        }

        $userDetails = $event->getParam(EventParameter::USER_DETAILS);
        $identity = $event->getParam(EventParameter::IDENTITY);
        $currentRoute = $event->getParam(EventParameter::CURRENT_ROUTE);

        if ($userDetails && $identity) {
            $result->setVariable('currentRouteName', $currentRoute);
            $result->setVariable('signedInUser', $userDetails);
            $result->setVariable(
                'secondsUntilSessionExpires',
                $identity->tokenExpiresAt()->getTimestamp() - $this->dateService->getToday()->getTimestamp()
            );

            foreach ($result->getChildren() as $child) {
                if ($child instanceof ViewModel && !$child instanceof JsonModel) {
                    $child->setVariable('currentRouteName', $currentRoute);
                }
            }
        }

        return null;
    }
}
