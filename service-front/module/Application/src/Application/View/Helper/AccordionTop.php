<?php
namespace Application\View\Helper;

use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;

class AccordionTop extends AbstractAccordion
{
    /**
     * @param Lpa $lpa
     * @return array|null
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
        $seq = 0;
        $barList = [];
        foreach($barConfig as $barRouteName => $barDataFuncName) {
            if($barRouteName == $flowChecker->check($barRouteName)) {
                if($barRouteName == $routeName) {
                    break;
                }
                else {
                    if(method_exists($this, $barDataFuncName)) {
                        $barList[$seq++] = [
                                'name'      => $this->getViewScriptName($barDataFuncName),
                                'routeName' => $barRouteName,
                                'lpaId'     => $lpa->id,
                                'params'    => [
                                    'idx'    => $seq,
                                    'values'=> $this->$barDataFuncName()]
                            ];
                    }
                }
            }
        }
        
        return $barList;
        
    }
}
