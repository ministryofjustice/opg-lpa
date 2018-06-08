<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use MongoDB\BSON\UTCDateTime as MongoDate;
use DateTime;

/**
 * Represents a single user.
 *
 * Class User
 * @package Application\Model\DataAccess\Mongo
 */
class User
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

    /**
     * Returns a DateTime for a given key from a range of time formats.
     *
     * @param $key
     * @return DateTime|boolean
     */
    private function returnDateField($key)
    {
        if (!isset($this->data[$key])) {
            return false;
        }

        if ($this->data[$key] instanceof DateTime) {
            return $this->data[$key];
        }

        if ($this->data[$key] instanceof MongoDate) {
            return $this->data[$key]->toDateTime();
        }

        return DateTime::createFromFormat('U', $this->data[$key]);
    }

    //---------------------------------------

    /**
     * Returns an array representation of the user's basic info.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'userId' => $this->id(),
            'username' => $this->username(),
            'isActive' => $this->isActive(),
            'lastLoginAt' => $this->lastLoginAt(),
            'updatedAt' => $this->updatedAt(),
            'createdAt' => $this->createdAt(),
            'activatedAt' => $this->activatedAt(),
            'lastFailedLoginAttemptAt' => $this->lastFailedLoginAttemptAt(),
            'failedLoginAttempts' => $this->failedLoginAttempts(),
        ];
    }

    //---------------------------------------

    /**
     * Returns the user's id.
     *
     * @return string
     */
    public function id()
    {
        return (isset($this->data['_id'])) ? $this->data['_id'] : null;
    }

    /**
     * Returns the user's username (email address).
     *
     * @return string
     */
    public function username()
    {
        return (isset($this->data['identity'])) ? $this->data['identity'] : null;
    }

    /**
     * Has the user's account been activated.
     *
     * @return bool
     */
    public function isActive()
    {
        if (!isset($this->data['active'])) {
            return false;
        }
        return ($this->data['active'] === true || $this->data['active'] === 'Y');
    }

    /**
     * The user's hashed password
     *
     * @return string
     */
    public function password()
    {
        return (isset($this->data['password_hash'])) ? $this->data['password_hash'] : null;
    }

    /**
     * The date the user's account was created.
     *
     * @return DateTime
     */
    public function createdAt()
    {
        return $this->returnDateField('created');
    }

    /**
     * The date the user's account was last updated.
     *
     * @return DateTime
     */
    public function updatedAt()
    {
        return $this->returnDateField('last_updated');
    }

    /**
     * The date the user's account is set to be deleted.
     *
     * @return DateTime
     */
    public function deleteAt()
    {
        return $this->returnDateField('deleteAt');
    }

    /**
     * The date the user's account was last successfully logged into.
     *
     * @return DateTime
     */
    public function lastLoginAt()
    {
        return $this->returnDateField('last_login');
    }

    /**
     * The date the user's account was activated.
     *
     * @return DateTime
     */
    public function activatedAt()
    {
        return $this->returnDateField('activated');
    }

    /**
     * The date the user's account was last unsuccessfully tied to be logged in to.
     *
     * @return DateTime
     */
    public function lastFailedLoginAttemptAt()
    {
        return $this->returnDateField('last_failed_login');
    }

    /**
     * The number of consecutive login attempts the user's account has received.
     *
     * @return int
     */
    public function failedLoginAttempts()
    {
        return (isset($this->data['failed_login_attempts'])) ? (int)$this->data['failed_login_attempts'] : 0;
    }

    /**
     * The user's activation token.
     *
     * @return string
     */
    public function activationToken()
    {
        return (isset($this->data['activation_token'])) ? $this->data['activation_token'] : null;
    }

    /**
     * Returns the user's current authentication token (if present).
     *
     * @return Token|null
     */
    public function authToken()
    {
        return (isset($this->data['auth_token'])) ? new Token($this->data['auth_token']) : null;
    }

    /**
     * Any account inactivity flags that may be set
     *
     * @return array|null
     */
    public function inactivityFlags()
    {
        return (isset($this->data['inactivity_flags'])) ? $this->data['inactivity_flags'] : null;
    }

    /**
     * Sets the failed login attempts to zero in this instance.
     * NOTE - this does not change the value in the database!
     */
    public function resetFailedLoginAttempts()
    {
        $this->data['failed_login_attempts'] = 0;
    }
}
