<?php

namespace Application\Model\Service\WhoIsRegistering;

use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\AccessorInterface as LpaAccessorInterface;

class Entity implements EntityInterface
{
    protected $who;

    public function __construct($who)
    {
        $this->who = $who;
    }

    /**
     * @return ((array|mixed)[]|string)[]
     *
     * @psalm-return array{whoIsRegistering?: array<array|mixed>|string}
     */
    public function toArray()
    {
        $who = [];

        if (is_string($this->who)) {
            $who = [
                'whoIsRegistering' => $this->who,
            ];
        } elseif (is_array($this->who)) {
            $who = [
                'whoIsRegistering' => array_map(function ($v) {
                    if ($v instanceof LpaAccessorInterface) {
                        return $v->toArray();
                    } else {
                        return $v;
                    }
                }, $this->who)
            ];
        }

        return $who;
    }
}
