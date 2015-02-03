<?php
namespace Application\View\Helper;

use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;

class AccordionTop extends AbstractAccordion
{
    public function __invoke (Lpa $lpa = null)
    {
        if($lpa === null) {
            return '';
        }
        
        $routeName = $this->getRouteName();
        $barConfig = $this->getBarConfig($routeName);
        
        if($barConfig == null) {
            return '';
        }
        
        $flowChecker = new FormFlowChecker($lpa);
        $seq = 0;
        $barList = [];
        
        foreach($barConfig as $barRouteName => $barTextSettings) {
            $seq++;
            if($barRouteName == $flowChecker->check($barRouteName)) {
                if($barRouteName == $routeName) {
                    $barList[$seq-1] = $seq.'. ' . $barTextSettings['active'];
                    break;
                }
                else {
                    $barList[$seq-1] = $seq.'. ' . $this->$barTextSettings['inactive']();
                }
            }
        }
        
        printr($barList);
        
        return '';
        
    }
}
