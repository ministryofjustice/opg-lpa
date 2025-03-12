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

    public function getData(): AbstractData
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data->toArray();
    }
}
