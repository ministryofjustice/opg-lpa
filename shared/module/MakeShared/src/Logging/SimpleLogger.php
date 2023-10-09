<?php

namespace MakeShared\Logging;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class SimpleLogger extends MonologLogger
{
    public function __construct()
    {
        parent::__construct();
        $this->pushHandler(new StreamHandler('php://stderr', Level::Warning));
    }
}
