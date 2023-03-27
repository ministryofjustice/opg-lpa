<?php

namespace Application\Model\Service\Applications;

use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Paginator\Paginator;
use Traversable;

/**
 * @template-extends Paginator<int,mixed>
 */
class Collection extends Paginator
{
    public function toArray()
    {
        //  Extract the applications data
        $applications = [];

        /** @var Traversable */
        $items = $this->getItemsByPage($this->getCurrentPageNumber());

        $lpas = iterator_to_array($items);

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
