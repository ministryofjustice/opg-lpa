<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Initializers\TemplatingSupportInterface;
use App\Handler\Initializers\TemplatingSupportTrait;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractHandler implements RequestHandlerInterface, TemplatingSupportInterface
{
    use TemplatingSupportTrait;
}
