<?php

namespace Application\Model\Service\Preference;

use Application\Model\Service\EntityInterface;

class Entity implements EntityInterface
{
    protected $preference;

    public function __construct($preference)
    {
        $this->preference = $preference;
    }

    /**
     * @return (false|string)[]
     *
     * @psalm-return array{preference?: false|string}
     */
    public function toArray()
    {
        if (is_string($this->preference) || $this->preference === false) {
            return [
                'preference' => $this->preference
            ];
        }

        return [];
    }
}
