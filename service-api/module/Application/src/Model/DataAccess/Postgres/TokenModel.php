<?php

namespace Application\Model\DataAccess\Postgres;

use DateMalformedStringException;
use DateTime;
use Application\Model\DataAccess\Repository\User as UserRepository;

class TokenModel implements UserRepository\TokenInterface
{
    /**
     * The token's data.
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
     * @param string $key
     *
     * @return DateTime|null
     *
     * @throws DateMalformedStringException
     */
    private function returnDateField(string $key)
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
     * Returns the token's id.
     *
     * @return string|null
     */
    public function id(): ?string
    {
        return (isset($this->data['token'])) ? $this->data['token'] : null;
    }

    /**
     * Date the token will current expire.
     *
     * @return DateTime|null
     */
    public function expiresAt(): ?DateTime
    {
        return $this->returnDateField('expiresAt');
    }

    /**
     * Date the token was last updated (extended).
     *
     * @return DateTime|null
     */
    public function updatedAt(): ?DateTime
    {
        return $this->returnDateField('updatedAt');
    }

    /**
     * Date the token was created.
     *
     * @return DateTime|null
     * @throws DateMalformedStringException
     * @psalm-api
     */
    public function createdAt(): ?DateTime
    {
        return $this->returnDateField('createdAt');
    }
}
