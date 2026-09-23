<?php

namespace Application\Library\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Header\Accept;
use Laminas\Http\Response;
use ArrayIterator;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemResponse;
use Laminas\Http\Header\HeaderInterface;

class ContentNegotiationListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1000)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], $priority);
    }

    public function onFinish(MvcEvent $e): void
    {
        $request = $e->getRequest();
        $response = $e->getResponse();

        // Type guard for HTTP requests/Responses only
        if (!$request instanceof HttpRequest || !$response instanceof Response) {
            return;
        }

        $requestAcceptHeader = $request->getHeader('accept');

        // Check if Accept header exists and is the correct type
        if (!$requestAcceptHeader instanceof Accept) {
            return;
        }

        // typically a response will only have one content-type header,
        // but just in case something weird happens we'll loop over the values
        /**
         * @var false|HeaderInterface|ArrayIterator $responseContentTypes
         */
        $responseContentTypes = $response->getHeaders()->get('content-type');
        if ($responseContentTypes === false) {
            // No content-type header at all usually means the response was
            // never actually produced by an API action - e.g. the MVC
            // dispatcher fell through to its built-in "not found"/error
            // handling (routing failure, missing controller action, etc.)
            // rather than reaching real controller code. That response
            // already carries the correct status (e.g. 404), so leave it
            // alone rather than masking it as a 406, which would hide the
            // real error from clients and make it much harder to debug.
            return;
        } elseif (!is_a($responseContentTypes, ArrayIterator::class)) {
            $responseContentTypes = new ArrayIterator([$responseContentTypes]);
        }

        $ok = false;
        foreach (iterator_to_array($responseContentTypes) as $responseContentType) {
            $responseContentTypeValue = $responseContentType->getFieldValue();

            if (
                !empty($responseContentTypeValue) &&
                $requestAcceptHeader->match($responseContentTypeValue)
            ) {
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            $e->setResponse(new ApiProblemResponse(
                new ApiProblem(406, 'Response has a content type which is not acceptable to the client')
            ));
        }
    }
}
