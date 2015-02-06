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

        $this->lpa = $lpa;
        
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
        foreach($barConfig as $barRouteName => $barDataFuncName) {
            if($barRouteName == $flowChecker->check($barRouteName)) {
                if($barRouteName == $routeName) {
                    $seq++;
                    $skip = false;
                    continue;
                }
                
                if($skip) {
                    $seq++;
                    continue;
                }
                
                $seq++;
                $barList[$seq-1] = $this->getView()->partial('layout/partials/accordion/accordion.phtml', 
                        ['name'=>$this->getViewScriptName($barDataFuncName), 'routeName'=>$barRouteName, 'lpaId'=>$lpa->id, 'params'=>['idx'=>$seq, 'values'=>$this->$barDataFuncName()]]);
            }
        }
        
        return implode('', $barList);
    }
}
