<?php
namespace Application\Logging;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\Event;
use Laminas\Mvc\MvcEvent;

use Application\Logging\LoggerTrait;
use Application\Logging\MvcEventProcessor;

/**
 * Listener for error events arising from controllers.
 */
class ErrorEventListener extends AbstractListenerAggregate
{
    use LoggerTrait;

    /**
     * Magic method so this class can be its own factory
     * @return EventLogger
     */
    public function __invoke(): self
    {
        return new self();
    }

    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($evt) {
            $this->onError(MvcEvent::EVENT_DISPATCH_ERROR, $evt);
        }, 100);

        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, function ($evt) {
            $this->onError(MvcEvent::EVENT_RENDER_ERROR, $evt);
        }, 100);
    }

    /**
     * Log an error
     *
     * @param string $errorType Identifier for the type of error
     * @param Event $event
     *
     * @return void
     */
    private function onError(string $errorType, Event $event): void
    {
        $extra = [
            MvcEventProcessor::EVENT_FIELD_NAME => $event,
        ];

        $this->getLogger()->err($errorType, $extra);
    }
}
