<?php

namespace Auth\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\AuthLogCollection;
use DateTime;

class UserManagementService extends AbstractService
{
    /**
     * @var AuthLogCollection
     */
    private $authLogCollection;

    /**
     * @param $userId
     * @return array|string
     */
    public function get($userId)
    {

        $user = $this->getAuthUserCollection()->getById($userId);

        if (is_null($user)) {
            return 'user-not-found';
        }

        return $user->toArray();
    }

    /**
     * @param string $username
     * @return array|bool
     */
    public function getByUsername(string $username)
    {
        $user = $this->getAuthUserCollection()->getByUsername($username);

        if (is_null($user)) {
            //Check if user has been deleted
            $identityHash = $this->hashIdentity($username);
            $deletionLog = $this->authLogCollection->getLogByIdentityHash($identityHash);

            if (is_null($deletionLog)) {
                return false;
            }

            return [
                'isDeleted' => true,
                'deletedAt' => $deletionLog['loggedAt']->toDateTime(),
                'reason' => $deletionLog['reason']
            ];
        }

        return $user->toArray();
    }

    /**
     * @param $userId
     * @param $reason
     * @return bool|string
     */
    public function delete($userId, $reason)
    {
        $user = $this->getAuthUserCollection()->getById($userId);

        if (is_null($user)) {
            return 'user-not-found';
        }

        //-------------------------------------------
        // Delete the user account

        $result = $this->getAuthUserCollection()->delete($userId);

        if ($result !== true) {
            return 'user-not-found';
        }

        //-------------------------------------------
        // Record the account deletion in the log

        $details = [
            'identity_hash' => $this->hashIdentity($user->username()),
            'type' => 'account-deleted',
            'reason' => $reason,
            'loggedAt' => new DateTime
        ];

        $this->authLogCollection->addLog($details);

        //---

        return true;
    }

    /**
     * Hashes the passed identity, ensuring it's trimmed and lowercase.
     *
     * @param $identity
     * @return string
     */
    private function hashIdentity($identity)
    {
        return hash('sha512', strtolower(trim($identity)));
    }

    /**
     * @param $authLogCollection
     */
    public function setAuthLogCollection($authLogCollection)
    {
        $this->authLogCollection = $authLogCollection;
    }
}
