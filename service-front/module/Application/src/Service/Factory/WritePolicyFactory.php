<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Session\WritePolicy;
use Psr\Container\ContainerInterface;

/**
 * In Mezzio context there is no Laminas\Http\PhpEnvironment\Request, so WritePolicy
 * is constructed without one. It falls back to checking $_SERVER['HTTP_X_SESSIONREADONLY']
 * directly, which is sufficient for PSR-7 usage.
 */
class WritePolicyFactory
{
    public function __invoke(ContainerInterface $container): WritePolicy
    {
        return new WritePolicy();
    }
}
