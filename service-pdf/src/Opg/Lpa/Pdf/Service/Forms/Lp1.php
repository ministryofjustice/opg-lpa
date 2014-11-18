<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\FormAbstract;
use Opg\Lpa\DataModel\Lpa\Formatter;

abstract class Lp1 extends FormAbstract
{
    protected function mapData()
    {
        $this->flattenLpa['lpa-id'] = Formatter::id($this->lpa->id);
        
        $donorDob = getdate(new \DateTime($this->flattenLpa['lpa-document-donor-dob-date']));
        $this->flattenLpa['lpa-document-donor-dob-date-day'] = $donorDob['day'];
        $this->flattenLpa['lpa-document-donor-dob-date-month'] = $donorDob['month'];
        $this->flattenLpa['lpa-document-donor-dob-date-year'] = $donorDob['year'];
        
        $noOfAttorneys = count($this->lpa->document->attroneys);
        if($noOfAttorneys == 1) {
            $this->flattenLpa['only-one-attorney-appointed'] = 'On';
            $this->flattenLpa['has-more-than-4-attorneys'] = 'Off';
        }
        elseif($noOfAttorneys > 4) {
            $this->flattenLpa['only-one-attorney-appointed'] = 'Off';
            $this->flattenLpa['has-more-than-4-attorneys'] = 'On';
        }
        
        
        
    }
} // class