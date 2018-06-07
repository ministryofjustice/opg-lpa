<?php

namespace Auth\Model\Service;

use DateTime;

class UserManagementService extends AbstractService
{
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
            $deletionLog = $this->getLogDataSource()->getLogByIdentityHash($identityHash);

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

        $this->getLogDataSource()->addLog($details);

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
}
