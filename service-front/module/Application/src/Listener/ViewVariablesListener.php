<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\Service\Date\DateService;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class ViewVariablesListener extends AbstractListenerAggregate
{
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

        $userDetails = $event->getParam(Attribute::USER_DETAILS);
        $identity = $event->getParam(Attribute::IDENTITY);

        if ($userDetails && $identity) {
            $result->setVariable('signedInUser', $userDetails);
            $result->setVariable(
                'secondsUntilSessionExpires',
                $identity->tokenExpiresAt()->getTimestamp() - $this->dateService->getToday()->getTimestamp()
            );
        }

        return null;
    }
}
