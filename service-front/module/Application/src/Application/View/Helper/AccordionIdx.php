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
        if ($lpa === null) {
            return 1;
        }
       
        $this->lpa = $lpa;
        
        $routeName = $this->getRouteName();
        
        if ($routeName == 'lpa-type-no-id') {
            // for the purposes of this function we'll treat the route
            // as lpa/form-type
            $routeName = 'lpa/form-type';
        }
        
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
