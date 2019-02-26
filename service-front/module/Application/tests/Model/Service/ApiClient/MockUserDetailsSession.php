<?php

namespace ApplicationTest\Model\Service\ApiClient;

use Interop\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * Mock for the UserDetailsSession as Mockery has poor support for the magic method used to get identity
 *
 * Class MockUserDetailsSession
 * @package ApplicationTest\Model\Service\ApiClient
 */
class MockUserDetailsSession implements ContainerInterface
{
    public $identity;

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        throw new RuntimeException('get($id) Not implemented');
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        throw new RuntimeException('has($id) Not implemented');
    }
}
