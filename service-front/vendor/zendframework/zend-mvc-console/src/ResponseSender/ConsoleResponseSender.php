<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console\ResponseSender;

use Zend\Console\Response;
use Zend\Mvc\ResponseSender\ResponseSenderInterface;
use Zend\Mvc\ResponseSender\SendResponseEvent;

class ConsoleResponseSender implements ResponseSenderInterface
{
    /**
     * Send content
     *
     * @param  SendResponseEvent $event
     * @return ConsoleResponseSender
     */
    public function sendContent(SendResponseEvent $event)
    {
        if ($event->contentSent()) {
            return $this;
        }
        $response = $event->getResponse();
        echo $response->getContent();
        $event->setContentSent();
        return $this;
    }

    /**
     * Send the response
     *
     * @param  SendResponseEvent $event
     */
    public function __invoke(SendResponseEvent $event)
    {
        $response = $event->getResponse();
        if (! $response instanceof Response) {
            return;
        }

        $this->sendContent($event);
        $errorLevel = (int) $response->getMetadata('errorLevel', 0);
        $event->stopPropagation(true);
        exit($errorLevel);
    }
}
