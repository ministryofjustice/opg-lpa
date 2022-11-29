<?php

namespace Application\Model\Service\Applications;

use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Paginator\Paginator;

class Collection extends Paginator
{
    public function toArray()
    {
        //  Extract the applications data
        $applications = [];

        $lpas = iterator_to_array($this->getItemsByPage($this->getCurrentPageNumber()));

        //  Get the full details of the LPA
        foreach ($lpas as $lpa) {
            /* @var $lpa Lpa */
            $applications[] = $lpa->toArray();
        }

        return [
            'applications' => $applications,
            'total'        => $this->getTotalItemCount(),
        ];
    }
}
