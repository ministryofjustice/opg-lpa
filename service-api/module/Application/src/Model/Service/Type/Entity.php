<?php

namespace Application\Model\Service\Type;

use Application\Model\Service\EntityInterface;

class Entity implements EntityInterface
{
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @return string[]
     *
     * @psalm-return array{type?: string}
     */
    public function toArray()
    {
        if (is_string($this->type)) {
            return [
                'type' => $this->type
            ];
        }

        return [];
    }
}
