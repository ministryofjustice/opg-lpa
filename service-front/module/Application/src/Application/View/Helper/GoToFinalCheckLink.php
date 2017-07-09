<?php

namespace Application\View\Helper;

use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Helper\AbstractHelper;

class GoToFinalCheckLink extends AbstractHelper
{
    private $finalCheckRoute = 'lpa/checkout';

    public function __invoke(Lpa $lpa)
    {
        $html = '';

        $flowChecker = new FormFlowChecker($lpa);

        if ($this->finalCheckRoute == $flowChecker->backToForm()) {
            $finalCheckUrl = $this->view->url($this->finalCheckRoute, [
                'lpa-id' => $lpa->id
            ]);

            $html = '<a href="' . $finalCheckUrl . '" class="button">Return to final check</a>';
        }

        return $html;
    }
}
