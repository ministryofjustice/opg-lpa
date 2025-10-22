<?php

namespace Application\Model\Service\Instruction;

use Application\Model\Service\EntityInterface;

class Entity implements EntityInterface
{
    protected $instruction;

    public function __construct($instruction)
    {
        $this->instruction = $instruction;
    }

    /**
     * @return (false|string)[]
     *
     * @psalm-return array{instruction?: false|string}
     */
    public function toArray()
    {
        if (is_string($this->instruction) || $this->instruction === false) {
            return [
                'instruction' => $this->instruction,
            ];
        }

        return [];
    }
}
