<?php
namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Application\Model\DataAccess\Repository\User as UserRepository;

class UserModel implements UserRepository\UserInterface
{

    /**
     * The user's data.
     *
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    //---------------------------------------

    /**
     * Returns an array representation of the user's basic info.
     *
     * @return array
     */
    public function toArray() : array
    {
        die(__METHOD__.' not implement');
    }

    //---------------------------------------

    /**
     * Returns the user's id.
     *
     * @return string
     */
    public function id() : ?string
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Returns the user's username (email address).
     *
     * @return string
     */
    public function username() : ?string
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Has the user's account been activated.
     *
     * @return bool
     */
    public function isActive() : bool
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The user's hashed password
     *
     * @return string
     */
    public function password() : ?string
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The date the user's account was created.
     *
     * @return DateTime
     */
    public function createdAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The date the user's account was last updated.
     *
     * @return DateTime
     */
    public function updatedAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The date the user's account is set to be deleted.
     *
     * @return DateTime
     */
    public function deleteAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The date the user's account was last successfully logged into.
     *
     * @return DateTime
     */
    public function lastLoginAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The date the user's account was activated.
     *
     * @return DateTime
     */
    public function activatedAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The date the user's account was last unsuccessfully tied to be logged in to.
     *
     * @return DateTime
     */
    public function lastFailedLoginAttemptAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The number of consecutive login attempts the user's account has received.
     *
     * @return int
     */
    public function failedLoginAttempts() : int
    {
        die(__METHOD__.' not implement');
    }

    /**
     * The user's activation token.
     *
     * @return string
     */
    public function activationToken() : ?string
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Returns the user's current authentication token (if present).
     *
     * @return UserRepository\TokenInterface|null
     */
    public function authToken() : ?UserRepository\TokenInterface
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Any account inactivity flags that may be set
     *
     * @return array|null
     */
    public function inactivityFlags() : ?array
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Sets the failed login attempts to zero in this instance.
     * NOTE - this does not change the value in the database!
     */
    public function resetFailedLoginAttempts()
    {
        die(__METHOD__.' not implement');
    }

}
