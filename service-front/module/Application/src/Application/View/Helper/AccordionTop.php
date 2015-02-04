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
        
        $this->lpa = $lpa;
        
        $routeName = $this->getRouteName();
        $barConfig = $this->getBarConfig($routeName);
        
        if($barConfig == null) {
            return '';
        }
        
        $flowChecker = new FormFlowChecker($lpa);
        $seq = 0;
        $barList = [];
        foreach($barConfig as $barRouteName => $barDataValues) {
            if($barRouteName == $flowChecker->check($barRouteName)) {
                if($barRouteName == $routeName) {
                    break;
                }
                else {
                    $barList[$seq++] = $this->view->partial('layout/partials/accordion/accordion.phtml', 
                            ['name'=>$this->getViewScriptName($barDataValues), 'params'=>['idx'=>$seq, 'values'=>$this->$barDataValues()]]);
                }
            }
        }
        
        return implode('', $barList);
        
    }
}
