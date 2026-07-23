<?php

namespace Application\Library\Authentication\Identity;

abstract class AbstractIdentity implements IdentityInterface
{
    protected $id = null;

    protected $roles = [];

    public function id()
    {
        return $this->id;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getId(): mixed
    {
        return $this->id;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getRoles(): iterable
    {
        return $this->roles;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
}
