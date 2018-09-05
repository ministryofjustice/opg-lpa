<?php
namespace Application\Model\DataAccess\Postgres;

use PDO;
use PDOException;
use DateTime;
use Opg\Lpa\DataModel\User\User as ProfileUserModel;
use Application\Model\DataAccess\Repository\User as UserRepository;

class UserData extends AbstractBase implements UserRepository\UserRepositoryInterface {

    const USERS_TABLE = 'users';

    /**
     * Returns a single user by username (email address).
     *
     * @param $username
     * @return UserRepository\UserInterface|null
     */
    public function getByUsername(string $username) : ?UserRepository\UserInterface
    {
        $sql = 'SELECT * FROM '.self::USERS_TABLE.' WHERE identity = :identity LIMIT 1';
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['identity' => $username]);
        $data = $stmt->fetch();

        if (!is_array($data)) {
            return null;
        }

        return new UserModel($data);
    }

    /**
     * @param $id
     * @return UserRepository\UserInterface|null
     */
    public function getById(string $id) : ?UserRepository\UserInterface
    {
        die(__METHOD__.' not implement');
    }

    /**
     * @param $token
     * @return UserRepository\UserInterface|null
     */
    public function getByAuthToken(string $token) : ?UserRepository\UserInterface
    {
        die(__METHOD__.' not implement');
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
        die(__METHOD__.' not implement');
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
        $fields = ['id', 'identity', 'password_hash', 'active', 'activation_token', 'created', 'updated', 'failed_login_attempts'];

        $sql  = 'INSERT INTO '.self::USERS_TABLE.'('.implode(', ', $fields).') VALUES(:'.implode(', :', $fields).')';
        $stmt = $this->getPdo()->prepare($sql);

        // Values are bound manually to sense check the data.
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->bindValue(':identity', $details['identity'], PDO::PARAM_STR);
        $stmt->bindValue(':active', $details['active'], PDO::PARAM_BOOL);
        $stmt->bindValue(':activation_token', $details['activation_token'], PDO::PARAM_STR);
        $stmt->bindValue(':password_hash', $details['password_hash'], PDO::PARAM_STR);
        $stmt->bindValue(':created', $details['created']->format(self::TIME_FORMAT), PDO::PARAM_STR);
        $stmt->bindValue(':updated', $details['last_updated']->format(self::TIME_FORMAT), PDO::PARAM_STR);
        $stmt->bindValue(':failed_login_attempts', $details['failed_login_attempts'], PDO::PARAM_INT);

        try {
            $stmt->execute();

        } catch (PDOException $e) {

            // If it's a key clash, and not on the identity, re-try with new values.
            if ($e->getCode() == 23505 && strpos($e->getMessage(), 'users_identity') === false) {
                return false;
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
        $sql = 'SELECT * FROM '.self::USERS_TABLE.' WHERE activation_token = :token LIMIT 1';
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!is_array($user)) {
            return false;
        }



        die(__METHOD__.' not implement');
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
        die(__METHOD__.' not implement');
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
        die(__METHOD__.' not implement');
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
     * @return UserModel
     */
    public function getProfile($id) : ?ProfileUserModel
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Updates a user's profile. If it doesn't already exist, it's created.
     *
     * @param UserModel $data
     * @return bool
     */
    public function saveProfile(ProfileUserModel $data) : bool
    {
        die(__METHOD__.' not implement');
    }

}
