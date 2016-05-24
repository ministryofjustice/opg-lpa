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
            if($barRouteName == $flowChecker->getNearestAccessibleRoute($barRouteName)) {
                if($barRouteName == $routeName) {
                    break;
                }
                else {
                    if(method_exists($this, $barDataFuncName)) {
                        $values = $this->$barDataFuncName();
                        if (!is_array($values)) {
                            $values = [
                                'text' => $values,
                                'count' => 0,
                            ];
                        }
                        $barList[$seq++] = [
                                'name'      => $this->getViewScriptName($barDataFuncName),
                                'routeName' => $barRouteName,
                                'lpaId'     => $lpa->id,
                                'params'    => [
                                    'idx'    => $seq,
                                    'status' => 'Complete',
                                    'values'=> $values['text'],
                                    'count' => $values['count'],
                                ]
                            ];
                    }
                }
            }
        }

        // FOR TESTING
//         return [
//             'lpa/form-type'                              ,
//             'lpa/donor'                                  ,
//             'lpa/when-lpa-starts'                        ,
//             'lpa/life-sustaining'                        ,
//             'lpa/primary-attorney'                       ,
//             'lpa/how-primary-attorneys-make-decision'    ,
//             'lpa/replacement-attorney'                   ,
//             'lpa/when-replacement-attorney-step-in'      ,
//             'lpa/how-replacement-attorneys-make-decision',
//             'lpa/certificate-provider'                   ,
//             'lpa/people-to-notify'                       ,
//             'lpa/instructions'                           ,
//         ];
        
        return $barList;
        
    }
}
