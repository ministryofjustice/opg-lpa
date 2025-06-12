<?php

namespace Application\Model\Service;

use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

abstract class AbstractService implements LoggerAwareInterface
{
    use LoggerTrait;
}
