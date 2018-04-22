<?php

namespace Application\View\Helper;

use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Helper\AbstractHelper;

class FinalCheckAccessible extends AbstractHelper
{
    public function __invoke(Lpa $lpa)
    {
        $flowChecker = new FormFlowChecker($lpa);

        return $flowChecker->finalCheckAccessible();
    }
}
