<?php

namespace Application\Model\Service\Applications;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Paginator\Paginator;

class Collection extends Paginator
{
    public function toArray()
    {
        //  Extract the applications data
        $applications = [];

        $lpas = iterator_to_array($this->getItemsByPage($this->getCurrentPageNumber()));

        //  Get the abbreviated details of the LPA
        foreach ($lpas as $lpa) {
            /** @var $lpa Lpa */
            $applications[] = $lpa->abbreviatedToArray();
        }

        return [
            'applications' => $applications,
            'total'        => $this->getTotalItemCount(),
        ];
    }
}
