<?php

namespace Application\Model\DataAccess\Postgres;

use DateMalformedStringException;
use DateTime;
use Application\Model\DataAccess\Repository\User as UserRepository;

class UserModel implements UserRepository\UserInterface
{
    /**
     * The user's data.
     */
    private array $data;

    public function __construct(array $data)
    {
        $defaults = [
            'numberOfLpas' => null,
            'sharedSpaceName' => null,
            'sharedSpaceId' => null,
            'isSharedSpaceAdmin' => null,
            'isActiveInSharedSpace' => null,
        ];

        $this->data = array_merge($defaults, $data);
    }

    //---------------------------------------

    /**
     * Returns a DateTime for a given key from a range of time formats.
     *
     * @throws DateMalformedStringException
     */
    private function returnDateField(string $key): ?DateTime
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
     * @inheritDoc
     * @throws DateMalformedStringException
     */
    public function toArray(): array
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
            'sharedSpaceName' => $this->sharedSpaceName(),
            'sharedSpaceId' => $this->sharedSpaceId(),
            'isSharedSpaceAdmin' => $this->isSharedSpaceAdmin(),
            'isActiveInSharedSpace' => $this->isActiveInSharedSpace(),
        ];
    }

    //---------------------------------------

    /**
     * @inheritDoc
     */
    public function id(): ?string
    {
        return (isset($this->data['id'])) ? $this->data['id'] : null;
    }

    /**
     * @inheritDoc
     */
    public function username(): ?string
    {
        return (isset($this->data['identity'])) ? $this->data['identity'] : null;
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        if (!isset($this->data['active'])) {
            return false;
        }
        return ($this->data['active'] === true || $this->data['active'] === 'Y');
    }

    /**
     * The user's hashed password
     *
     * @return string|null
     */
    public function password(): ?string
    {
        return (isset($this->data['password_hash'])) ? $this->data['password_hash'] : null;
    }

    /**
     * @inheritDoc
     * @throws DateMalformedStringException
     */
    public function createdAt(): ?DateTime
    {
        return $this->returnDateField('created');
    }

    /**
     * @inheritDoc
     * @throws DateMalformedStringException
     */
    public function updatedAt(): ?DateTime
    {
        return $this->returnDateField('updated');
    }

    /**
     * @inheritDoc
     * @throws DateMalformedStringException
     */
    public function lastLoginAt(): ?DateTime
    {
        return $this->returnDateField('last_login');
    }

    /**
     * @inheritDoc
     * @throws DateMalformedStringException
     */
    public function activatedAt(): ?DateTime
    {
        return $this->returnDateField('activated');
    }

    /**
     * @inheritDoc
     * @throws DateMalformedStringException
     */
    public function lastFailedLoginAttemptAt(): ?DateTime
    {
        return $this->returnDateField('last_failed_login');
    }

    /**
     * @inheritDoc
     */
    public function failedLoginAttempts(): int
    {
        return (isset($this->data['failed_login_attempts'])) ? (int)$this->data['failed_login_attempts'] : 0;
    }

    /**
     * @inheritDoc
     */
    public function activationToken(): ?string
    {
        return (isset($this->data['activation_token'])) ? $this->data['activation_token'] : null;
    }

    /**
     * @inheritDoc
     */
    public function oneLoginSub(): ?string
    {
        return (isset($this->data['one_login_sub'])) ? $this->data['one_login_sub'] : null;
    }

    /**
     * @inheritDoc
     */
    public function oneLoginEmail(): ?string
    {
        return (isset($this->data['one_login_email'])) ? $this->data['one_login_email'] : null;
    }

    /**
     * The address the service contacts this user on: the One Login address when
     * we hold one, otherwise the login email (`identity`).
     *
     * @return string|null
     */
    public function contactEmail(): ?string
    {
        $oneLoginEmail = $this->oneLoginEmail();

        return ($oneLoginEmail !== null && $oneLoginEmail !== '') ? $oneLoginEmail : $this->username();
    }

    /**
     * @inheritDoc
     */
    public function authToken(): ?UserRepository\TokenInterface
    {
        if (!isset($this->data['auth_token'])) {
            return null;
        }

        return new TokenModel(json_decode($this->data['auth_token'], true));
    }

    /**
     * @inheritDoc
     */
    public function inactivityFlags(): ?array
    {
        if (!isset($this->data['inactivity_flags'])) {
            return null;
        }

        return json_decode($this->data['inactivity_flags'], true);
    }

    /**
     * Sets the failed login attempts to zero in this instance.
     * NOTE - this does not change the value in the database!
     *
     * @return void
     */
    public function resetFailedLoginAttempts()
    {
        $this->data['failed_login_attempts'] = 0;
    }

    /**
     * @inheritDoc
     */
    public function numberOfLpas(): ?int
    {
        $value = $this->data['numberOfLpas'];

        if (is_null($value)) {
            return $value;
        }

        return intval($value);
    }

    /**
     * @inheritDoc
     */
    public function sharedSpaceName(): ?string
    {
        return (isset($this->data['sharedSpaceName'])) ? $this->data['sharedSpaceName'] : null;
    }

    /**
     * @inheritDoc
     */
    public function sharedSpaceId(): ?string
    {
        return (isset($this->data['sharedSpaceId'])) ? $this->data['sharedSpaceId'] : null;
    }

    /**
     * @inheritDoc
     */
    public function isSharedSpaceAdmin(): ?bool
    {
        if (!isset($this->data['isSharedSpaceAdmin'])) {
            return null;
        }

        return ($this->data['isSharedSpaceAdmin'] === true || $this->data['isSharedSpaceAdmin'] === 'Y');
    }

    /**
     * @inheritDoc
     */
    public function isActiveInSharedSpace(): ?bool
    {
        if (!isset($this->data['isActiveInSharedSpace'])) {
            return null;
        }

        return ($this->data['isActiveInSharedSpace'] === true || $this->data['isActiveInSharedSpace'] === 'Y');
    }
}
