<?php
namespace Application\View\Helper;

use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;

class AccordionBottom extends AbstractAccordion
{
    public function __invoke (Lpa $lpa = null)
    {
        if($lpa === null) {
            return '';
        }
        
        $routeName = $this->getRouteName();
        $barConfig = $this->getBarConfig($routeName);
        
        // empty if the route does not have accordions.
        if($barConfig == null) {
            return '';
        }
        
        $flowChecker = new FormFlowChecker($lpa);
        $seq = 0;
        $barList = [];
        
        $skip = true;
        foreach($barConfig as $barRouteName => $barTextSettings) {
            if($barRouteName == $routeName) {
                $seq++;
                $skip = false;
                continue;
            }
            
            if($skip) {
                $seq++;
                continue;
            }
            
            if($barRouteName == $flowChecker->check($barRouteName)) {
                $seq++;
                $barList[$seq-1] = $seq.'. ' . $this->$barTextSettings['inactive']();
            }
        }
        
        printr($barList);
        
        return '';
        
    }
}
