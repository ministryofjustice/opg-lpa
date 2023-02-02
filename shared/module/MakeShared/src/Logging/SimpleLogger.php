<?php

namespace MakeShared\Logging;

use Laminas\Log\Logger as LaminasLogger;
use Laminas\Log\Writer\Stream as StreamWriter;

class SimpleLogger extends LaminasLogger
{
    public function __construct()
    {
        parent::__construct();
        $this->addWriter(new StreamWriter('php://stderr'));
    }
}
