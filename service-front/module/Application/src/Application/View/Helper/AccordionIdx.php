<?php
namespace Application\View\Helper;

use Opg\Lpa\DataModel\Lpa\Lpa;

class AccordionIdx extends AbstractAccordion
{
    public function __invoke (Lpa $lpa = null)
    {
        
        $this->lpa = $lpa;
        
        return 'VI';
        
    }
}
