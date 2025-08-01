<?php

namespace App\Logging;

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
     * @param ServerRequestInterface $_1 (unused)
     * @param ResponseInterface $_2 (unused)
     * @return void
     */
    public function __invoke(Throwable $error, ServerRequestInterface $_1, ResponseInterface $_2)
    {
        $this->getLogger()->error(sprintf(
            '%s in %s on line %s - %s',
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            $error->getTraceAsString()
        ));
    }
}
