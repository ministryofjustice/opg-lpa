<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use MongoDB\BSON\UTCDateTime as MongoDate;
use DateTime;

/**
 * Represents a single token.
 *
 * Class User
 * @package Application\Model\DataAccess\Mongo
 */
class Token
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
     * @param $key
     * @return DateTime
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
     * Returns the token's id.
     *
     * @return string
     */
    public function id()
    {
        return (isset($this->data['token'])) ? $this->data['token'] : null;
    }

    /**
     * Returns the owner of the token's user id.
     *
     * @return mixed
     */
    public function user()
    {
        return (isset($this->data['user'])) ? $this->data['user'] : null;
    }

    /**
     * Date the token will current expire.
     *
     * @return DateTime
     */
    public function expiresAt()
    {
        return $this->returnDateField('expiresAt');
    }

    /**
     * Date the token was last updated (extended).
     *
     * @return DateTime
     */
    public function updatedAt()
    {
        return $this->returnDateField('updatedAt');
    }

    /**
     * Date the token was created.
     *
     * @return DateTime
     */
    public function createdAt()
    {
        return $this->returnDateField('createdAt');
    }
}
