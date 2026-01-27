<?php

namespace Application\Model\DataAccess\Postgres;

use Application\Model\DataAccess\Repository\User\UpdateEmailUsingTokenResponse;
use DateMalformedStringException;
use Exception;
use PDOException;
use DateTime;
use Laminas\Db\Sql\Expression as SqlExpression;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Predicate\IsNull;
use Laminas\Db\Sql\Predicate\IsNotNull;
use Laminas\Db\Adapter\Exception\RuntimeException as LaminasDbAdapterRuntimeException;
use MakeShared\DataModel\User\User as ProfileUserModel;
use Application\Model\DataAccess\Postgres\AbstractBase;
use Application\Model\DataAccess\Postgres\ApplicationData;
use Application\Model\DataAccess\Repository\User as UserRepository;

class UserData extends AbstractBase implements UserRepository\UserRepositoryInterface
{
    public const USERS_TABLE = 'users';

    //-----------------------------------------------------------
    // Helpers

    /**
     * Returns a single user by a given field name and associated value.
     *
     * @param array $where
     * @return array|null
     */
    private function getByField(array $where): ?array
    {
        try {
            $result = $this->dbWrapper->select(self::USERS_TABLE, $where, ['limit' => 1]);
        } catch (LaminasDbAdapterRuntimeException $e) {
            // this occurs where the select against the db fails
            return null;
        }

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
    private function countRows(array $where): int
    {
        $options = [
            'columns' => [
                'count' => new Expression('COUNT(*)')
            ]
         ];

        $result = $this->dbWrapper->select(self::USERS_TABLE, $where, $options);

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
    private function updateRow(array $where, array $set): bool
    {
        $sql = $this->dbWrapper->createSql();
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
    public function getByUsername(string $username): ?UserRepository\UserInterface
    {
        $user = $this->getByField(['identity' => $username]);

        if (!is_array($user)) {
            return null;
        }

        return new UserModel($user);
    }

    /**
     * Returns zero or more users by case-insensitive and partial
     * matching
     *
     * @param $query
     * @param $options - array of optional parameters, including
     * 'offset' (int, default 0) and 'limit' (int, default 10)
     * @return iterable UserModel instances
     */
    public function matchUsers(string $query, array $options = []): iterable
    {
        $offset = 0;
        $limit = 10;

        if (isset($options['offset'])) {
            $offset = intval($options['offset']);
        }

        if (isset($options['limit'])) {
            $limit = intval($options['limit']);
        }

        $sql = $this->dbWrapper->createSql();

        // count applications by user
        $subselect = $sql->select()->from(['a' => ApplicationData::APPLICATIONS_TABLE])
            ->columns(['user', 'numberOfLpas' => new SqlExpression('COUNT(*)')])
            ->group(['user']);

        // case-insensitive match on user email
        $queryQuoted = $this->dbWrapper->quoteValue(sprintf('%%%s%%', $query));
        $like = new Expression('u.identity ILIKE ' . $queryQuoted);

        // main query
        // WARNING join type is "FULL" here as using Select::JOIN_OUTER produces
        // invalid SQL; but this potentially locks the code to Postgres
        $select = $sql->select()->from(['u' => self::USERS_TABLE])
            ->join(
                ['a' => $subselect],
                'u.id = a.user',
                ['numberOfLpas'],
                'FULL'
            )
            ->where($like)
            ->order('identity ASC')
            ->offset($offset)
            ->limit($limit);

        $users = $sql->prepareStatementForSqlObject($select)->execute();

        foreach ($users as $user) {
            yield new UserModel($user);
        }
    }

    /**
     * @param $id
     * @return UserRepository\UserInterface|null
     */
    public function getById(string $id): ?UserRepository\UserInterface
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
    public function getByAuthToken(string $token): ?UserRepository\UserInterface
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
    public function getByResetToken(string $token): ?UserRepository\UserInterface
    {
        $user = $this->getByField([new Expression("password_reset_token ->> 'token' = ?", $token)]);

        if (!is_array($user)) {
            return null;
        }

        return new UserModel($user);
    }

    /**
     * @param string $id
     */
    public function updateLastLoginTime(string $id): void
    {
        $this->updateRow(
            ['id' => $id],
            [
                'last_login' => gmdate(DbWrapper::TIME_FORMAT),
                'inactivity_flags' => null,
            ]
        );
    }

    /**
     * Resets the user's failed login counter to zero.
     *
     * @param string $id
     */
    public function resetFailedLoginCounter(string $id): void
    {
        $this->updateRow(
            ['id' => $id],
            [
                'failed_login_attempts' => 0,
            ]
        );
    }

    /**
     * Increments the user's failed login counter by 1.
     *
     * @param string $id
     */
    public function incrementFailedLoginCounter(string $id): void
    {
        $this->updateRow(
            ['id' => $id],
            [
                'last_failed_login' => gmdate(DbWrapper::TIME_FORMAT),
                'failed_login_attempts' => new Expression('failed_login_attempts + 1'),
            ]
        );
    }

    /**
     * Creates a new user account
     *
     * If false is returned, we will try again with different userId and activation token.
     *
     * @param string $id
     * @param array $details
     * @return bool
     * @throws Exception
     */
    public function create(string $id, array $details): bool
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::USERS_TABLE);

        $data = [
            'id' => $id,
            'identity' => $details['identity'],
            'password_hash' => $details['password_hash'],
            'activation_token' => $details['activation_token'],
            'active' => $details['active'],
            'created' => $details['created']->format(DbWrapper::TIME_FORMAT),
            'updated' => $details['last_updated']->format(DbWrapper::TIME_FORMAT),
            'failed_login_attempts' => $details['failed_login_attempts']
        ];

        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

        try {
            $statement->execute();
        } catch (\Laminas\Db\Adapter\Exception\InvalidQueryException $e) {
            $previousException = $e->getPrevious();

            // If it's a key clash, and not on the identity, re-try with new values.
            if ($previousException instanceof PDOException) {
                if (
                    $previousException->getCode() == 23505 &&
                    strpos($previousException->getMessage(), 'users_identity') === false
                ) {
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
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->updateRow(
            ['id' => $id],
            [
                'deleted' => gmdate(DbWrapper::TIME_FORMAT),
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
     * @param string $token
     * @return bool
     */
    public function activate(string $token): bool
    {
        return $this->updateRow(
            ['activation_token' => $token],
            [
                'active' => true,
                'updated' => gmdate(DbWrapper::TIME_FORMAT),
                'activated' => gmdate(DbWrapper::TIME_FORMAT),
                'activation_token' => null,
            ]
        );
    }

    /**
     * Updates a user's password.
     *
     * @param string $userId
     * @param string $passwordHash
     */
    public function setNewPassword(string $userId, string $passwordHash): void
    {
        $this->updateRow(
            ['id' => $userId],
            [
                'password_hash' => $passwordHash,
                'updated' => gmdate(DbWrapper::TIME_FORMAT),
                'auth_token' => null,
            ]
        );
    }

    /**
     * Sets a new auth token.
     *
     * @param string $userId
     * @param DateTime $expires
     * @param string $token
     * @return bool
     */
    public function setAuthToken(string $userId, DateTime $expires, string $token): bool
    {
        return $this->updateRow(
            ['id' => $userId],
            [
                'auth_token' => json_encode([
                    'token' => $token,
                    'createdAt' => gmdate(DbWrapper::TIME_FORMAT),
                    'updatedAt' => gmdate(DbWrapper::TIME_FORMAT),
                    'expiresAt' => $expires->format(DbWrapper::TIME_FORMAT),
                ]),
            ]
        );
    }

    /**
     * Sets the expiry datetime of the authentication token.
     *
     * @param $userId
     * @param DateTime $expires
     * @return bool
     */
    public function updateAuthTokenExpiry(string $userId, DateTime $expires): bool
    {
        return $this->updateRow(
            ['id' => $userId],
            [
                'auth_token' => new Expression("auth_token || ?", json_encode([
                    'updatedAt' => gmdate(DbWrapper::TIME_FORMAT),
                    'expiresAt' => $expires->format(DbWrapper::TIME_FORMAT),
                ]))
            ]
        );
    }

    /**
     * @param string $id
     * @param array $token
     */
    public function addPasswordResetToken(string $id, array $token): void
    {
        // Map DateTimes to Strings
        $token = array_map(function ($v) {
            return ($v instanceof DateTime) ? $v->format(DbWrapper::TIME_FORMAT) : $v;
        }, $token);

        $this->updateRow(
            ['id' => $id],
            [
                'password_reset_token' => json_encode($token),
            ]
        );
    }

    /**
     * @param $token
     * @param $passwordHash
     * @return ?UserRepository\UpdatePasswordUsingTokenError
     */
    public function updatePasswordUsingToken(
        string $token,
        string $passwordHash
    ): ?UserRepository\UpdatePasswordUsingTokenError {
        $result = $this->updateRow(
            [new Expression("password_reset_token ->> 'token' = ?", $token)],
            [
                'password_reset_token' => null,
                'password_hash' => $passwordHash,
                'updated' => gmdate(DbWrapper::TIME_FORMAT),
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
     * @param string $id
     * @param array $token
     * @param string $newEmail
     */
    public function addEmailUpdateTokenAndNewEmail(string $id, array $token, string $newEmail): void
    {
        // Map DateTimes to Strings
        $token = array_map(function ($v) {
            return ($v instanceof DateTime) ? $v->format(DbWrapper::TIME_FORMAT) : $v;
        }, $token);

        $this->updateRow(
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
     * @param string $token
     * @return UpdateEmailUsingTokenResponse
     * @throws DateMalformedStringException
     */
    public function updateEmailUsingToken(string $token): UserRepository\UpdateEmailUsingTokenResponse
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
                'updated' => gmdate(DbWrapper::TIME_FORMAT),
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
     * If $excludeFlag is set, accounts that contain the passed flag will be excluded.
     *
     * @param DateTime $since
     * @param string|null $excludeFlag
     * @return iterable
     */
    public function getAccountsInactiveSince(DateTime $since, ?string $excludeFlag = null): iterable
    {
        $where = [
            new Operator('last_login', Operator::OPERATOR_LESS_THAN, $since->format('c'))
        ];

        // Exclude results that have already been actioned
        if (!is_null($excludeFlag)) {
            $where[] = new Expression("inactivity_flags -> '{$excludeFlag}' IS NULL");
        }

        $users = $this->dbWrapper->select(self::USERS_TABLE, $where);

        foreach ($users as $user) {
            yield new UserModel($user);
        }
    }

    /**
     * Adds a new inactivity flag to an account.
     *
     * @param string $userId
     * @param string $flag
     */
    public function setInactivityFlag(string $userId, string $flag): void
    {
        $this->updateRow(
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
    public function getAccountsUnactivatedOlderThan(DateTime $olderThan): iterable
    {
        $where = [
            'active' => false,
            new Operator('created', Operator::OPERATOR_LESS_THAN, $olderThan->format('c')),
        ];

        $users = $this->dbWrapper->select(self::USERS_TABLE, $where);

        foreach ($users as $user) {
            yield new UserModel($user);
        }
    }

    /**
     * Counts the number of account in the system. (Excludes deleted accounts)
     *
     * @return int Account count
     */
    public function countAccounts(): int
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
    public function countActivatedAccounts(DateTime|null $since = null): int
    {
        if (is_null($since)) {
            // All activated accounts have an activation date.
            $where = [new IsNotNull('activated')];
        } else {
            $where = [
                new Operator(
                    'activated',
                    Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
                    $since->format(DbWrapper::TIME_FORMAT)
                )
            ];
        }

        return $this->countRows($where);
    }

    /**
     * Counts the number of accounts that have been deleted.
     *
     * @return int Account count
     */
    public function countDeletedAccounts(): int
    {
        // Deleted accounts have a row, but no identity
        return $this->countRows([new IsNull('identity')]);
    }

    /**
     * Return a user's profile details
     *
     * @param $id
     * @return ?ProfileUserModel
     */
    public function getProfile($id): ?ProfileUserModel
    {
        $user = $this->getByField(['id' => $id]);

        if (!is_array($user) || !isset($user['profile'])) {
            return null;
        }

        // Map fields needed from the top level (user), into the profile.
        $profile = array_merge(json_decode($user['profile'], true), [
            'id' => $id,
            'createdAt' => $user['created'],
            'updatedAt' => $user['updated'],
            'lastLoginAt' => $user['last_login'],
        ]);

        return new ProfileUserModel($profile);
    }

    /**
     * Updates a user's profile. If it doesn't already exist, it's created.
     *
     * @param ProfileUserModel $data
     */
    public function saveProfile(ProfileUserModel $data): void
    {
        $user = $data->toArray();

        // Remove unwarned fields
        unset($user['id'], $user['createdAt'], $user['updatedAt']);

        $this->updateRow(
            ['id' => $data->getId()],
            [
                'profile' => json_encode($user),
            ]
        );
    }
}
