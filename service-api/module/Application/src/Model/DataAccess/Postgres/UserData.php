<?php
namespace Application\Model\DataAccess\Postgres;

use PDOException;
use DateTime;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\IsNull;
use Zend\Db\Sql\Predicate\IsNotNull;
use Opg\Lpa\DataModel\User\User as ProfileUserModel;
use Application\Model\DataAccess\Repository\User as UserRepository;

class UserData extends AbstractBase implements UserRepository\UserRepositoryInterface {

    const USERS_TABLE = 'users';

    //-----------------------------------------------------------
    // Helpers

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
     * Counts the number of row that match the passed where clause.
     *
     * @param array $where
     * @return int
     */
    private function countRows(array $where) : int
    {
        $sql = new Sql($this->getZendDb());

        $select = $sql->select(self::USERS_TABLE);

        $select->columns(['count' => new Expression('count(*)')]);

        $select->where($where);

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$result->isQueryResult() || $result->count() != 1) {
            return 0;
        }

        return $result->current()['count'];
    }

    /**
     * Updates a single row. Returns true on success, otherwise false.
     *
     * @param array $where
     * @param array $set
     * @return bool
     */
    private function updateRow(array $where, array $set) : bool
    {
        $sql = new Sql($this->getZendDb());
        $update = $sql->update(self::USERS_TABLE);
        $update->where($where);

        $update->set($set);

        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();

        return $results->getAffectedRows() === 1;
    }

    //-----------------------------------------------------------

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
        $user = $this->getByField([new Expression("password_reset_token ->> 'token' = ?", $token)]);

        if (!is_array($user)) {
            return null;
        }

        return new UserModel($user);
    }

    /**
     * @param $id
     * @return bool
     */
    public function updateLastLoginTime(string $id) : bool
    {
        return  $this->updateRow(
            ['id' => $id],
            [
                'last_login' => gmdate(self::TIME_FORMAT),
                'inactivity_flags' => null,
            ]
        );
    }

    /**
     * Resets the user's failed login counter to zero.
     *
     * @param $id
     * @return bool
     */
    public function resetFailedLoginCounter(string $id) : bool
    {
        return  $this->updateRow(
            ['id' => $id],
            [
                'failed_login_attempts' => 0,
            ]
        );
    }

    /**
     * Increments the user's failed login counter by 1.
     *
     * @param $id
     * @return bool
     */
    public function incrementFailedLoginCounter(string $id) : bool
    {
        return  $this->updateRow(
            ['id' => $id],
            [
                'last_failed_login' => gmdate(self::TIME_FORMAT),
                'failed_login_attempts' => new Expression('failed_login_attempts + 1'),
            ]
        );
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
        $insert = $sql->insert(self::USERS_TABLE);

        $data = [
            'id'                    => $id,
            'identity'              => $details['identity'],
            'password_hash'         => $details['password_hash'],
            'activation_token'      => $details['activation_token'],
            'active'                => $details['active'],
            'created'               => $details['created']->format(self::TIME_FORMAT),
            'updated'               => $details['last_updated']->format(self::TIME_FORMAT),
            'failed_login_attempts' => $details['failed_login_attempts']
        ];

        $insert->columns(array_keys($data));

        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

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
        return $this->updateRow(
            ['id' => $id],
            [
                'deleted' => gmdate(self::TIME_FORMAT),
                'active' => null,
                'identity' => null,
                'password_hash' => null,
                'activation_token' => null,
                'failed_login_attempts' => null,
                'created' => null,
                'updated' => null,
                'activated' => null,
                'last_login' => null,
                'last_failed_login' => null,
                'inactivity_flags' => null,
                'auth_token' => null,
                'email_update_request' => null,
                'password_reset_token' => null,
                'profile' => null,
            ]
        );
    }

    /**
     * Activates a user account
     *
     * @param $token
     * @return bool|null
     */
    public function activate(string $token) : bool
    {
        return $this->updateRow(
            ['activation_token' => $token],
            [
                'active' => true,
                'updated' => gmdate(self::TIME_FORMAT),
                'activated' => gmdate(self::TIME_FORMAT),
                'activation_token' => null,
            ]
        );
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
        return $this->updateRow(
            ['id' => $userId],
            [
                'password_hash' => $passwordHash,
                'updated' => gmdate(self::TIME_FORMAT),
                'auth_token' => null,
            ]
        );
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
        return $this->updateRow(
            ['id' => $userId],
            [
                'auth_token' => json_encode([
                    'token' => $token,
                    'createdAt' => gmdate(self::TIME_FORMAT),
                    'updatedAt' => gmdate(self::TIME_FORMAT),
                    'expiresAt' => $expires->format(self::TIME_FORMAT),
                ]),
            ]
        );
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
        return  $this->updateRow(
            ['id' => $userId],
            [
                'auth_token' => new Expression("auth_token || ?", json_encode([
                    'updatedAt' => gmdate(self::TIME_FORMAT),
                    'expiresAt' => $expires->format(self::TIME_FORMAT),
                ]))
            ]
        );
    }

    /**
     * @param $id
     * @param array $token
     * @return bool
     */
    public function addPasswordResetToken(string $id, array $token) : bool
    {
        // Map DateTimes to Strings
        $token = array_map(function ($v) {
            return ($v instanceof DateTime) ? $v->format(self::TIME_FORMAT) : $v;
        }, $token);

        return $this->updateRow(
            ['id' => $id],
            [
                'password_reset_token' => json_encode($token),
            ]
        );
    }

    /**
     * @param $token
     * @param $passwordHash
     * @return UserRepository\UpdatePasswordUsingTokenError
     */
    public function updatePasswordUsingToken(string $token, string $passwordHash) : ?UserRepository\UpdatePasswordUsingTokenError
    {
        $result = $this->updateRow(
            [new Expression("password_reset_token ->> 'token' = ?", $token)],
            [
                'password_reset_token' => null,
                'password_hash' => $passwordHash,
                'updated' => gmdate(self::TIME_FORMAT),
                'auth_token' => null,
            ]
        );

        if (!$result) {
            return new UserRepository\UpdatePasswordUsingTokenError('nothing-modified');
        }

        // All went well; no error to return.
        return null;
    }

    /**
     * @param $id
     * @param array $token
     * @param $newEmail
     * @return bool
     */
    public function addEmailUpdateTokenAndNewEmail(string $id, array $token, string $newEmail) : bool
    {
        // Map DateTimes to Strings
        $token = array_map(function ($v) {
            return ($v instanceof DateTime) ? $v->format(self::TIME_FORMAT) : $v;
        }, $token);

        return $this->updateRow(
            ['id' => $id],
            [
                'email_update_request' => json_encode([
                    'token' => $token,
                    'email' => $newEmail,
                ]),
            ]
        );
    }

    /**
     * @param $token
     * @return UserRepository\UpdateEmailUsingTokenResponse
     */
    public function updateEmailUsingToken(string $token) : UserRepository\UpdateEmailUsingTokenResponse
    {
        $user = $this->getByField([new Expression("email_update_request -> 'token' ->> 'token' = ?", $token)]);

        if (!is_array($user)) {
            return new UserRepository\UpdateEmailUsingTokenResponse('invalid-token');
        }

        $request = json_decode($user['email_update_request'], true);
        $expires = new DateTime($request['token']['expiresAt']);

        if ($expires < new DateTime()) {
            // Token has expired
            return new UserRepository\UpdateEmailUsingTokenResponse('invalid-token');
        }

        //-------------

        $newEmail = $request['email'];

        $clashUser = $this->getByField(['identity' => $newEmail]);

        if (is_array($clashUser)) {
            return new UserRepository\UpdateEmailUsingTokenResponse('username-already-exists');
        }

        //-------------

        $result = $this->updateRow(
            ['id' => $user['id']],
            [
                'identity' => $newEmail,
                'updated' => gmdate(self::TIME_FORMAT),
                'email_update_request' => null,
            ]
        );

        if (!$result) {
            return new UserRepository\UpdateEmailUsingTokenResponse('nothing-modified');
        }

        // Returns the User, wrapped in a Response object.
        return new UserRepository\UpdateEmailUsingTokenResponse(new UserModel($user));
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
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::USERS_TABLE);

        $select->where([
            new Operator('last_login', Operator::OPERATOR_LESS_THAN, $since->format('c'))
        ]);

        // Exclude results that have already been actioned.
        if (!is_null($excludeFlag)) {
            $select->where([
                new Expression("inactivity_flags -> '{$excludeFlag}' IS NULL")
            ]);
        }

        $users = $sql->prepareStatementForSqlObject($select)->execute();

        foreach ($users as $user) {
            yield new UserModel($user);
        }
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
        return $this->updateRow(
            ['id' => $userId],
            [
                'inactivity_flags' => new Expression(
                    // Adds the flag to the object. If the object does exist, create it.
                    "(CASE WHEN inactivity_flags IS NULL THEN '{}'::JSONB ELSE inactivity_flags END) || ?",
                    json_encode([$flag => true])
                )
            ]
        );
    }

    /**
     * Returns all accounts create before date $olderThan and that have not been activated.
     *
     * @param DateTime $olderThan
     * @return iterable
     */
    public function getAccountsUnactivatedOlderThan(DateTime $olderThan) : iterable
    {
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::USERS_TABLE);

        $select->where([
            'active' => false,
            new Operator('created', Operator::OPERATOR_LESS_THAN, $olderThan->format('c')),
        ]);

        $users = $sql->prepareStatementForSqlObject($select)->execute();

        foreach ($users as $user) {
            yield new UserModel($user);
        }
    }

    /**
     * Counts the number of account in the system. (Excludes deleted accounts)
     *
     * @return int Account count
     */
    public function countAccounts() : int
    {
        // All 'live' accounts have an identity
        return $this->countRows([new IsNotNull('identity')]);
    }

    /**
     * Counts the number of ACTIVATED account in the system.
     *
     * @param DateTime|null $since only include accounts activated $since
     * @return int Account count
     */
    public function countActivatedAccounts(DateTime $since = null) : int
    {
        if (is_null($since)) {
            // All activated accounts have an activation date.
            $where = [new IsNotNull('activated')];

        } else {
            $where = [new Operator('activated', Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $since->format('c'))];
        }

        return $this->countRows($where);
    }

    /**
     * Counts the number of accounts that have been deleted.
     *
     * @return int Account count
     */
    public function countDeletedAccounts() : int
    {
        // Deleted accounts have a row, but no identity
        return $this->countRows([new IsNull('identity')]);
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
     * @param ProfileUserModel $user
     * @return bool
     */
    public function saveProfile(ProfileUserModel $user) : bool
    {
        $data = $user->toArray();

        // Remove unwarned fields
        unset($data['id'], $data['createdAt'], $data['updatedAt']);

        return $this->updateRow(
            ['id'=>$user->getId()],
            [
                'profile' => json_encode($data),
            ]
        );
    }

}
