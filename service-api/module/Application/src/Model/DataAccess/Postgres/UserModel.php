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
        // if the numberOfLpas key hasn't been set, set it to null
        // to mark that the value hasn't been derived
        if (!array_key_exists('numberOfLpas', $data)) {
            $data['numberOfLpas'] = null;
        }

        $this->data = $data;
    }

    //---------------------------------------

    /**
     * Returns a DateTime for a given key from a range of time formats.
     *
     * @param $key
     * @return DateTime|null
     */
    private function returnDateField($key)
    {
        if (!isset($this->data[$key])) {
            return null;
        }

        if ($this->data[$key] instanceof DateTime) {
            return $this->data[$key];
        }

        return new DateTime($this->data[$key]);
    }

    //---------------------------------------

    /**
     * Returns an array representation of the user's basic info.
     *
     * @return array
     */
    public function toArray() : array
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
            'numberOfLpas' => $this->numberOfLpas(),
        ];
    }

    //---------------------------------------

    /**
     * Returns the user's id.
     *
     * @return string
     */
    public function id() : ?string
    {
        return (isset($this->data['id'])) ? $this->data['id'] : null;
    }

    /**
     * Returns the user's username (email address).
     *
     * @return string
     */
    public function username() : ?string
    {
        return (isset($this->data['identity'])) ? $this->data['identity'] : null;
    }

    /**
     * Has the user's account been activated.
     *
     * @return bool
     */
    public function isActive() : bool
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
    public function password() : ?string
    {
        return (isset($this->data['password_hash'])) ? $this->data['password_hash'] : null;
    }

    /**
     * The date the user's account was created.
     *
     * @return DateTime
     */
    public function createdAt() : ?DateTime
    {
        return $this->returnDateField('created');
    }

    /**
     * The date the user's account was last updated.
     *
     * @return DateTime
     */
    public function updatedAt() : ?DateTime
    {
        return $this->returnDateField('updated');
    }

    /**
     * The date the user's account is set to be deleted.
     *
     * @return DateTime
     */
    public function deleteAt() : ?DateTime
    {
        return $this->returnDateField('deleted');
    }

    /**
     * The date the user's account was last successfully logged into.
     *
     * @return DateTime
     */
    public function lastLoginAt() : ?DateTime
    {
        return $this->returnDateField('last_login');
    }

    /**
     * The date the user's account was activated.
     *
     * @return DateTime
     */
    public function activatedAt() : ?DateTime
    {
        return $this->returnDateField('activated');
    }

    /**
     * The date the user's account was last unsuccessfully tied to be logged in to.
     *
     * @return DateTime
     */
    public function lastFailedLoginAttemptAt() : ?DateTime
    {
        return $this->returnDateField('last_failed_login');
    }

    /**
     * The number of consecutive login attempts the user's account has received.
     *
     * @return int
     */
    public function failedLoginAttempts() : int
    {
        return (isset($this->data['failed_login_attempts'])) ? (int)$this->data['failed_login_attempts'] : 0;
    }

    /**
     * The user's activation token.
     *
     * @return string
     */
    public function activationToken() : ?string
    {
        return (isset($this->data['activation_token'])) ? $this->data['activation_token'] : null;
    }

    /**
     * Returns the user's current authentication token (if present).
     *
     * @return UserRepository\TokenInterface|null
     */
    public function authToken() : ?UserRepository\TokenInterface
    {
        if (!isset($this->data['auth_token'])) {
            return null;
        }

        return new TokenModel(json_decode($this->data['auth_token'], true));
    }

    /**
     * Any account inactivity flags that may be set
     *
     * @return array|null
     */
    public function inactivityFlags() : ?array
    {
        if (!isset($this->data['inactivity_flags'])) {
            return null;
        }

        return json_decode($this->data['inactivity_flags'], true);
    }

    /**
     * Sets the failed login attempts to zero in this instance.
     * NOTE - this does not change the value in the database!
     */
    public function resetFailedLoginAttempts()
    {
        $this->data['failed_login_attempts'] = 0;
    }

    /**
     * Number of LPA applications made by the user.
     * If not set, returns null.
     *
     * @return int|null
     */
    public function numberOfLpas()
    {
        $value = $this->data['numberOfLpas'];

        if (is_null($value)) {
            return $value;
        }

        return intval($value);
    }
}
