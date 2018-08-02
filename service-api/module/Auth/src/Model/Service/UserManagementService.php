<?php

namespace Auth\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\AuthLogCollection;
use Zend\Validator\EmailAddress as EmailAddressValidator;
use Zend\Math\BigInteger\BigInteger;
use DateTime;

class UserManagementService extends AbstractService
{
    use PasswordValidatorTrait;

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
     * @param $username
     * @param $password
     * @return array|string
     */
    public function create($username, $password)
    {
        $emailValidator = new EmailAddressValidator();

        if (!$emailValidator->isValid($username)) {
            return 'invalid-username';
        }

        //  Check the username isn't already used...
        $user = $this->getAuthUserCollection()->getByUsername($username);

        if (!is_null($user)) {
            return 'username-already-exists';
        }

        if (!$this->isPasswordValid($password)) {
            return 'invalid-password';
        }

        //  Create the account
        //  We use a loop here to ensure we retry to create the account if there's
        //  a clash with the userId or activation_token (despite this being extremely unlikely).
        do {
            // Create a 32 character user id and activation token.

            $userId = bin2hex(openssl_random_pseudo_bytes(16));
            $activationToken = bin2hex(openssl_random_pseudo_bytes(16));

            // Use base62 for shorter tokens
            $activationToken = BigInteger::factory('bcmath')->baseConvert($activationToken, 16, 62);

            $created = (bool)$this->getAuthUserCollection()->create($userId, [
                'identity' => $username,
                'active' => false,
                'activation_token' => $activationToken,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created' => new DateTime(),
                'last_updated' => new DateTime(),
                'failed_login_attempts' => 0,
            ]);
        } while (!$created);

        return [
            'userId' => $userId,
            'activation_token' => $activationToken,
        ];
    }

    /**
     * @param $token
     * @return bool|string
     */
    public function activate($token)
    {
        $result = $this->getAuthUserCollection()->activate($token);

        if (is_null($result) || $result === false) {
            return 'account-not-found';
        }

        return true;
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

        $result = $this->getAuthUserCollection()->delete($userId);

        if ($result !== true) {
            return 'user-not-found';
        }

        // Record the account deletion in the log
        $details = [
            'identity_hash' => $this->hashIdentity($user->username()),
            'type' => 'account-deleted',
            'reason' => $reason,
            'loggedAt' => new DateTime
        ];

        $this->authLogCollection->addLog($details);

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
    public function setAuthLogCollection(AuthLogCollection $authLogCollection)
    {
        $this->authLogCollection = $authLogCollection;
    }
}
