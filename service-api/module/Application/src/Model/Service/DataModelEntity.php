<?php

namespace Application\Model\Service;

use MakeShared\DataModel\AbstractData;

class DataModelEntity implements EntityInterface
{
    protected $data;

    public function __construct(AbstractData $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toArray()
    {
        return $this->data->toArray();
    }
}
