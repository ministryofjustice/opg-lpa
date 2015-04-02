<?php
namespace Application\View\Helper;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Model\FormFlowChecker;

class AccordionIdx extends AbstractAccordion
{
    /**
     * @param Lpa $lpa
     * @return int|null
     */
    public function __invoke (Lpa $lpa = null)
    {
        if($lpa === null) {
            return null;
        }
        
        $this->lpa = $lpa;
        
        $routeName = $this->getRouteName();
        $barConfig = $this->getBarConfig($routeName);
        
        if($barConfig == null) {
            return null;
        }
        
        $flowChecker = new FormFlowChecker($lpa);
        $seq = 1;
        $barList = [];
        foreach($barConfig as $barRouteName => $barDataValues) {
            if($barRouteName == $flowChecker->getNearestAccessibleRoute($barRouteName)) {
                if($barRouteName == $routeName) {
                    break;
                }
                else {
                    $seq++;
                }
            }
        }
        
        return $seq;
    }
}
