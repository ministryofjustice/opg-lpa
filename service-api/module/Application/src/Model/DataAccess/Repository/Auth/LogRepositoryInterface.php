<?php
namespace Application\Model\DataAccess\Repository\Auth;

interface LogRepositoryInterface
{

    /**
     * Add a document to the log collection.
     *
     * @param array $details
     * @return bool
     */
    public function addLog(array $details) : bool;

    /**
     * Retrieve a log document based on the identity hash stored against it
     *
     * @param string $identityHash
     * @return array
     */
    public function getLogByIdentityHash(string $identityHash) : ?array;

}
