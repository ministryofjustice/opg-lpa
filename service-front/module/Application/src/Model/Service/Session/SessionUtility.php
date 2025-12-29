<?php

declare(strict_types=1);

namespace Application\Model\Service\Session;

use Laminas\Session\Container as LaminasContainer;
use Mezzio\Session\SessionInterface as MezzioSession;

class SessionUtility
{
    private function getMvcContainer(string $containerName): LaminasContainer
    {
        return new LaminasContainer($containerName);
    }

    public function getFromMvc(
        string $containerName,
        string $key,
        mixed $default = null
    ): mixed {
        $container = $this->getMvcContainer($containerName);
        return $container->$key ?? $default;
    }

    public function setInMvc(
        string $containerName,
        string $key,
        mixed $value
    ): void {
        $container = $this->getMvcContainer($containerName);
        $container->$key = $value;
    }

    public function unsetInMvc(
        string $containerName,
        string $key
    ): void {
        $container = $this->getMvcContainer($containerName);
        unset($container->$key);
    }

    public function hasInMvc(
        string $containerName,
        string $key
    ): bool {
        $container = $this->getMvcContainer($containerName);
        return isset($container->$key);
    }

    public function setExpirationHopsInMvc(
        string $containerName,
        int $hops
    ): void {
        $container = $this->getMvcContainer($containerName);
        $container->setExpirationHops($hops);
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
