<?php

namespace App\Logging;

use MakeLogger\Logger\LoggerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Class LoggingErrorListener
 * @package App\Logging
 */
class LoggingErrorListener
{
    use LoggerTrait;

    /**
     * @param Throwable $error
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function __invoke(Throwable $error, ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->getLogger()->err(sprintf(
            '%s in %s on line %s - %s' . $error->getTraceAsString(),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            $error->getTraceAsString()
        ));
    }
}
