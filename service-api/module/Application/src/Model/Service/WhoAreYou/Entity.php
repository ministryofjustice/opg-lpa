<?php

namespace Application\Model\Service\WhoAreYou;

use Application\Model\Service\EntityInterface;

class Entity implements EntityInterface
{
    protected $answered;

    public function __construct($answered)
    {
        $this->answered = $answered;
    }

    public function toArray()
    {
        if (is_bool($this->answered)) {
            return [
                'whoAreYouAnswered' => $this->answered,
            ];
        }

        return [];
    }
}
