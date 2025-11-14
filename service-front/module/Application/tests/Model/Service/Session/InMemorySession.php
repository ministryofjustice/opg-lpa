<?php

namespace ApplicationTest\Model\Service\Session;

use Mezzio\Session\SessionInterface;

final class InMemorySession implements SessionInterface
{
    private array $data = [];
    private bool $changed = false;
    private bool $regenerated = false;
    private string $id = 'test';

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    public function set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
        $this->changed = true;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function unset(string $name): void
    {
        if (array_key_exists($name, $this->data)) {
            unset($this->data[$name]);
            $this->changed = true;
        }
    }

    public function clear(): void
    {
        $this->data = [];
        $this->changed = true;
    }

    public function regenerate(): SessionInterface
    {
        $this->regenerated = true;
        // fake a new id for tests
        $this->id = bin2hex(random_bytes(8));
        return $this;
    }

    public function persistSessionFor(int $duration): SessionInterface
    {
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function hasChanged(): bool
    {
        return $this->changed;
    }

    public function isRegenerated(): bool
    {
        return $this->regenerated;
    }
}
