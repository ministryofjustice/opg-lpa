<?php

namespace Application\Model\DataAccess\Repository\User;

interface LogRepositoryInterface
{
    /**
     * Add a document to the log collection.
     *
     * @param array $details
     */
    public function addLog(array $details): void;

    /**
     * Retrieve a log document based on the identity hash stored against it
     *
     * @param string $identityHash
     * @return array
     */
    public function getLogByIdentityHash(string $identityHash): ?array;
}
