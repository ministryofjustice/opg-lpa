<?php

namespace Application\Model\Service\Session;

use Laminas\Session\Container as LaminasContainer;
use Mezzio\Session\SessionInterface as MezzioSession;

final class SessionUtility
{
    public function getFromMvc(
        string $containerName,
        string $key,
        mixed $default = null
    ): mixed {
        $container = new LaminasContainer($containerName);
        return $container->$key ?? $default;
    }

    public function setInMvc(
        string $containerName,
        string $key,
        mixed $value
    ): void {
        $container = new LaminasContainer($containerName);
        $container->$key = $value;
    }

    public function unsetInMvc(
        string $containerName,
        string $key
    ): void {
        $container = new LaminasContainer($containerName);
        unset($container->$key);
    }

    public function getFromMezzio(
        MezzioSession $session,
        string $key,
        mixed $default = null
    ): mixed {
        return $session->get($key, $default);
    }

    public function setInMezzio(
        MezzioSession $session,
        string $key,
        mixed $value
    ): void {
        $session->set($key, $value);
    }

    public function unsetInMezzio(
        MezzioSession $session,
        string $key
    ): void {
        $session->unset($key);
    }

    public function hasInMezzio(
        MezzioSession $session,
        string $key
    ): bool {
        return $session->has($key);
    }
}
