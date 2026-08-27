<?php

namespace Application\Library\Listener;

use Laminas\Mvc\MvcEvent;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;

class ErrorListener extends AbstractListenerAggregate
{
    // priority is set to 100 here so that the global MvcEventListener
    // has a chance to log it before it's converted into an API exception
    public function attach(EventManagerInterface $events, $priority = 100)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'onError'], 100);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'onError'], 100);
    }

    /**
     * Listen for and catch ApiProblemExceptions. Convert them to a standard ApiProblemResponse.
     */
    public function onError(MvcEvent $e): ?ApiProblemResponse
    {
        $response = null;

        // Marshall an ApiProblem and view model based on the exception
        $exception = $e->getParam('exception');

        if ($exception instanceof ApiProblemExceptionInterface) {
            $problem = new ApiProblem($exception->getCode(), $exception->getMessage());

            $e->stopPropagation();
            $response = new ApiProblemResponse($problem);
            $e->setResponse($response);
        } elseif ($exception) {
            $logger = $e->getApplication()->getServiceManager()->get('Logger');
            $logger->error($exception->getMessage(), [
                'class' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stackTrace' => $exception->getTraceAsString(),
            ]);
        }

        return $response;
    }
}
