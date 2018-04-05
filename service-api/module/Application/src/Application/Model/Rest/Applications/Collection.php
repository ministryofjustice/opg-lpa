<?php

namespace Application\Model\Rest\Applications;

use Application\Model\Rest\CollectionInterface;
use Zend\Paginator\Paginator;

class Collection extends Paginator implements CollectionInterface
{
    protected $userId;

    public function __construct($adapter, $userId)
    {
        parent::__construct($adapter);

        $this->userId = $userId;
    }

    public function userId()
    {
        return $this->userId;
    }

    public function lpaId()
    {
        return null;
    }

    public function resourceId()
    {
        return null;
    }

    public function toArray()
    {
        //  Extract the applications data
        $applications = [];

        $applicationsArr = iterator_to_array($this->getItemsByPage($this->getCurrentPageNumber()));

        //  For each item first set the value in an abbreviated entity then extract
        foreach ($applicationsArr as $applicationArr) {
            $abbreviatedApplication = new AbbreviatedEntity($applicationArr);
            $applications[] = $abbreviatedApplication->toArray();
        }

        return [
            'applications' => $applications,
            'total'        => $this->getTotalItemCount(),
        ];
    }
}
