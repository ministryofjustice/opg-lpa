<?php
namespace Application\Model\DataAccess\Postgres;

use PDOException;
use DateTime;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Opg\Lpa\DataModel\User\User as ProfileUserModel;
use Application\Model\DataAccess\Repository\User as UserRepository;

class UserData extends AbstractBase implements UserRepository\UserRepositoryInterface {

    const USERS_TABLE = 'users';

    /**
     * Returns a single user by a given field name and associated value.
     *
     * @param array $where
     * @return array|null
     */
    private function getByField(array $where) : ?array
    {
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::USERS_TABLE);
        $select->where($where);
        $select->limit(1);

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$result->isQueryResult() || $result->count() != 1) {
            return null;
        }

        return $result->current();
    }

    /**
     * Returns a single user by username (email address).
     *
     * @param $username
     * @return UserRepository\UserInterface|null
     */
    public function getByUsername(string $username) : ?UserRepository\UserInterface
    {
        $user = $this->getByField(['identity' => $username]);

        if (!is_array($user)) {
            return null;
        }

        return new UserModel($user);
    }

    /**
     * @param $id
     * @return UserRepository\UserInterface|null
     */
    public function getById(string $id) : ?UserRepository\UserInterface
    {
        $user = $this->getByField(['id' => $id]);

        if (!is_array($user)) {
            return null;
        }

        return new UserModel($user);
    }

    /**
     * @param $token
     * @return UserRepository\UserInterface|null
     */
    public function getByAuthToken(string $token) : ?UserRepository\UserInterface
    {
        $user = $this->getByField([new Expression("auth_token ->> 'token' = ?", $token)]);

        if (!is_array($user)) {
            return null;
        }

        return new UserModel($user);
    }

    /**
     * @param $token
     * @return UserRepository\UserInterface|null
     */
    public function getByResetToken(string $token) : ?UserRepository\UserInterface
    {
        die(__METHOD__.' not implement');
    }

    /**
     * @param $id
     * @return bool
     */
    public function updateLastLoginTime(string $id) : bool
    {
        $sql = new Sql($this->getZendDb());
        $update = $sql->update(self::USERS_TABLE);
        $update->where(['id'=>$id]);

        $update->set([
            'last_login' => gmdate(self::TIME_FORMAT),
            'inactivity_flags' => null,
        ]);

        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();

        return $results->getAffectedRows() === 1;
    }

    /**
     * Resets the user's failed login counter to zero.
     *
     * @param $id
     * @return bool
     */
    public function resetFailedLoginCounter(string $id) : bool
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Increments the user's failed login counter by 1.
     *
     * @param $id
     * @return bool
     */
    public function incrementFailedLoginCounter(string $id) : bool
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Creates a new user account
     *
     * If false is returned, we will try again with different userId and activation token.
     *
     * @param $id
     * @param array $details
     * @throws \Exception
     * @return bool
     */
    public function create(string $id, array $details) : bool
    {
        $sql = new Sql($this->getZendDb());
        $update = $sql->insert(self::USERS_TABLE);

        $update->columns(['id', 'identity', 'password_hash', 'active', 'activation_token', 'created', 'updated', 'failed_login_attempts']);

        $update->values([
            'id'                    => $id,
            'identity'              => $details['identity'],
            'password_hash'         => $details['password_hash'],
            'activation_token'      => $details['activation_token'],
            'active'                => $details['active'],
            'created'               => $details['created']->format(self::TIME_FORMAT),
            'updated'               => $details['last_updated']->format(self::TIME_FORMAT),
            'failed_login_attempts' => $details['failed_login_attempts']
        ]);

        $statement = $sql->prepareStatementForSqlObject($update);

        try {
            $statement->execute();

        } catch (\Zend\Db\Adapter\Exception\InvalidQueryException $e){

            // If it's a key clash, and not on the identity, re-try with new values.
            if ($e->getPrevious() instanceof PDOException) {
                $pdoException = $e->getPrevious();

                if ($pdoException->getCode() == 23505 && strpos($pdoException->getMessage(), 'users_identity') === false) {
                    return false;
                }
            }

            // Otherwise re-throw the exception
            throw($e);
        }

        // All ended well
        return true;
    }

    /**
     * Delete the account for the passed user.
     *
     * NB: When an account is deleted, the document it kept, leaving only _id and a new deletedAt field.
     *
     * @param $id
     * @return bool|null
     */
    public function delete(string $id) : bool
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Activates a user account
     *
     * @param $token
     * @return bool|null
     */
    public function activate(string $token) : bool
    {
        $sql = new Sql($this->getZendDb());
        $update = $sql->update(self::USERS_TABLE);
        $update->where(['activation_token'=>$token]);

        $update->set([
            'active' => true,
            'updated' => gmdate(self::TIME_FORMAT),
            'activated' => gmdate(self::TIME_FORMAT),
            'activation_token' => null,
        ]);

        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();

        return $results->getAffectedRows() === 1;
    }

    /**
     * Updates a user's password.
     *
     * @param $userId
     * @param $passwordHash
     * @return bool
     */
    public function setNewPassword(string $userId, string $passwordHash) : bool
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Sets a new auth token.
     *
     * @param $userId
     * @param DateTime $expires
     * @param $token
     * @return bool
     */
    public function setAuthToken(string $userId, DateTime $expires, string $token) : bool
    {
        $sql = new Sql($this->getZendDb());
        $update = $sql->update(self::USERS_TABLE);
        $update->where(['id'=>$userId]);

        $update->set([
            'auth_token' => json_encode([
                'token' => $token,
                'createdAt' => gmdate(self::TIME_FORMAT),
                'updatedAt' => gmdate(self::TIME_FORMAT),
                'expiresAt' => $expires->format(self::TIME_FORMAT),
            ]),
        ]);

        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();

        return $results->getAffectedRows() === 1;
    }

    /**
     * Extends the authentication token.
     *
     * @param $userId
     * @param DateTime $expires
     * @return bool
     */
    public function extendAuthToken(string $userId, DateTime $expires) : bool
    {
        $sql = new Sql($this->getZendDb());
        $update = $sql->update(self::USERS_TABLE);
        $update->where(['id'=>$userId]);

        // Merges the new times into the existing JSON
        $update->set(['auth_token' => new Expression("auth_token || ?", json_encode([
            'updatedAt' => gmdate(self::TIME_FORMAT),
            'expiresAt' => $expires->format(self::TIME_FORMAT),
        ]))]);

        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();

        return $results->getAffectedRows() === 1;
    }

    /**
     * @param $id
     * @param array $token
     * @return bool
     */
    public function addPasswordResetToken(string $id, array $token) : bool
    {
        die(__METHOD__.' not implement');
    }

    /**
     * @param $token
     * @param $passwordHash
     * @return UserRepository\UpdatePasswordUsingTokenError
     */
    public function updatePasswordUsingToken(string $token, string $passwordHash) : ?UserRepository\UpdatePasswordUsingTokenError
    {
        die(__METHOD__.' not implement');
    }

    /**
     * @param $id
     * @param array $token
     * @param $newEmail
     * @return bool
     */
    public function addEmailUpdateTokenAndNewEmail(string $id, array $token, string $newEmail) : bool
    {
        die(__METHOD__.' not implement');
    }

    /**
     * @param $token
     * @return UserRepository\UpdateEmailUsingTokenResponse
     */
    public function updateEmailUsingToken(string $token) : UserRepository\UpdateEmailUsingTokenResponse
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Returns all accounts that have not been logged into since $since.
     *
     * If $withoutFlag is set, accounts that contain the passed flag will be excluded.
     *
     * @param DateTime $since
     * @param string $excludeFlag
     * @return iterable
     */
    public function getAccountsInactiveSince(DateTime $since, ?string $excludeFlag = null) : iterable
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Adds a new inactivity flag to an account.
     *
     * @param $userId
     * @param $flag
     * @return bool
     */
    public function setInactivityFlag(string $userId, string $flag) : bool
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Returns all accounts create before date $olderThan and that have not been activated.
     *
     * @param DateTime $olderThan
     * @return iterable
     */
    public function getAccountsUnactivatedOlderThan(DateTime $olderThan) : iterable
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Counts the number of account in the system.
     *
     * @return int Account count
     */
    public function countAccounts() : int
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Counts the number of ACTIVATED account in the system.
     *
     * @param DateTime|null $since only include accounts activated $since
     * @return int Account count
     */
    public function countActivatedAccounts(DateTime $since = null) : int
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Counts the number of accounts that have been deleted.
     *
     * @return int Account count
     */
    public function countDeletedAccounts() : int
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Return a user's profile details
     *
     * @param $id
     * @return ProfileUserModel
     */
    public function getProfile($id) : ?ProfileUserModel
    {
        $user = $this->getByField(['id' => $id]);

        if (!is_array($user) || !isset($user['profile'])) {
            return null;
        }

        // Map fields needed from the top level (user), into the profile.
        $profile = array_merge(json_decode($user['profile'], true), [
            'id'=>$id,
            'createdAt'=>$user['created'],
            'updatedAt'=>$user['updated']
        ]);

        return new ProfileUserModel($profile);
    }

    /**
     * Updates a user's profile. If it doesn't already exist, it's created.
     *
     * @param ProfileUserModel $data
     * @return bool
     */
    public function saveProfile(ProfileUserModel $data) : bool
    {
        $sql = new Sql($this->getZendDb());
        $update = $sql->update(self::USERS_TABLE);
        $update->where(['id'=>$data->getId()]);

        $data = $data->toArray();

        // Remove unwarned fields
        unset($data['id'], $data['createdAt'], $data['updatedAt']);

        $update->set([
            'profile' => json_encode($data),
        ]);

        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();

        return $results->getAffectedRows() === 1;
    }

}
