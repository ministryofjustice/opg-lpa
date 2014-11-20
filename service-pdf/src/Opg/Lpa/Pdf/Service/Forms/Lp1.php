<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

abstract class Lp1 extends AbstractForm
{
    protected function mapData()
    {
        $this->flattenLpa['lpa-id'] = Formatter::id($this->lpa->id);
        
        $this->flattenLpa['lpa-document-donor-dob-date-day'] =  $this->lpa->document->donor->dob->date->format('d');
        $this->flattenLpa['lpa-document-donor-dob-date-month'] = $this->lpa->document->donor->dob->date->format('m');
        $this->flattenLpa['lpa-document-donor-dob-date-year'] = $this->lpa->document->donor->dob->date->format('Y');
        
        // attorneys section (section 2)
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys == 1) {
            $this->flattenLpa['only-one-attorney-appointed'] = 'On';
        }
        elseif($noOfPrimaryAttorneys > 4) {
            $this->flattenLpa['has-more-than-4-attorneys'] = 'On';
            
            //@todo fill CS1
        }
        
        // populate attorney dob
        for($i=0; $i<$noOfPrimaryAttorneys; +$i++) {
            if($this->lpa->document->primaryAttorneys[$i] instanceof TrustCorporation) continue;
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-day'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-year'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('Y');
            if($i==3) break;
        }
        
        // attorney decision section (section 3)
        if($noOfPrimaryAttorneys > 1) {
            switch($this->flattenLpa['lpa-document-decisions-how']) {
                case Decisions::LPA_DECISION_HOW_JOINTLY:
                    $this->flattenLpa['attorneys-act-jointly'] = 'On';
                    break;
                case Decisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    $this->flattenLpa['attorneys-act-jointly-and-severally'] = 'On';
                    break;
                case Decisions::LPA_DECISION_HOW_MIXED:
                    $this->flattenLpa['attorneys-act-upon-decisions'] = 'On';
                    
                    //@todo fill CS2
                    break;
                default:
                    break;
            }
        }
        
        // replacement attorneys section (section 4)
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $this->flattenLpa['has-more-than-2-replacement-attorneys'] = 'On';
        
            //@todo fill CS1
        }
        
        // populate replacement attorney dob (section 4)
        for($i=0; $i<$noOfReplacementAttorneys; +$i++) {
            if($this->lpa->document->replacementAttorneys[$i] instanceof TrustCorporation) continue;
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-day'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-month'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-year'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('Y');
            if($i==1) break;
        }
        
        // @todo how replacements step in and act. fill CS2 and tick $this->flattenLpa['change-how-replacement-attorneys-step-in']
        
        // People to notify (Section 6)
        if(count($this->lpa->document->peopleToNotify) > 4) {
            $this->flattenLpa['has-more-than-5-notified-people'] = 'On';
            
            //@todo fill CS1
        }
        
        // @todo: calculate characters in Preference and Instructions boxes and split to CS2. (Section 7)
        
        // Populate primary and replacement attorneys signature pages (Section 11)
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $i=0;
        foreach($allAttorneys as $attorney) {
            if($attorney instanceof TrustCorporation) continue;
            $this->flattenLpa['signature-attorney-'.$i.'-name-title'] = $attorney->name->title;
            $this->flattenLpa['signature-attorney-'.$i.'-name-first'] = $attorney->name->first;
            $this->flattenLpa['signature-attorney-'.$i.'-name-last'] = $attorney->name->last;
            $i++;
            
            // @todo dup pages field name special rule applies
        }
        
        
        // Applicant (Section 12)
        if($this->flattenLpa['lpa-document-whoIsRegistering'] == 'donor') {
            $this->flattenLpa['donor-is-applicant'] = 'On';
        }
        else {
            for($i=0; $i<count($this->flattenLpa['lpa-document-whoIsRegistering']); $i++) {
                $this->flattenLpa['applicant-'.$i.'-name-title'] = $this->flattenLpa['lpa-document-whoIsRegistering'][$i]->name->title;
                $this->flattenLpa['applicant-'.$i.'-name-first'] = $this->flattenLpa['lpa-document-whoIsRegistering'][$i]->name->first;
                $this->flattenLpa['applicant-'.$i.'-name-first'] = $this->flattenLpa['lpa-document-whoIsRegistering'][$i]->name->last;
                $this->flattenLpa['applicant-'.$i.'-dob-date-day'] = $this->flattenLpa['lpa-document-whoIsRegistering'][$i]->dob->date->format('d');
                $this->flattenLpa['applicant-'.$i.'-dob-date-month'] = $this->flattenLpa['lpa-document-whoIsRegistering'][$i]->dob->date->format('m');
                $this->flattenLpa['applicant-'.$i.'-dob-date-year'] = $this->flattenLpa['lpa-document-whoIsRegistering'][$i]->dob->date->format('Y');
                
                // @todo dup pages field name special rule applies
            }
        }
        
        // Correspondent (Section 13)
        switch($this->flattenLpa['lpa-document-correspondent-who']) {
            case Correspondence::WHO_DONOR:
                $this->flattenLpa['donor-is-correspondent'] = 'On';
                break;
            case Correspondence::WHO_ATTORNEY:
                $this->flattenLpa['attorney-is-correspondent'] = 'On';
                break;
            case Correspondence::WHO_OTHER:
                $this->flattenLpa['other-is-correspondent'] = 'On';
                break;
        }
        
        if(isset($this->flattenLpa['lpa-document-correspondent-contactByPost'])) {
            $this->flattenLpa['correspondent-contact-by-post'] = 'On';
        }
        
        if(isset($this->flattenLpa['lpa-document-correspondent-phone-number'])) {
            $this->flattenLpa['correspondent-contact-by-phone'] = 'On';
        }
        
        if(isset($this->flattenLpa['lpa-document-correspondent-email-address'])) {
            $this->flattenLpa['correspondent-contact-by-email'] = 'On';
        }
        
        if(isset($this->flattenLpa['lpa-document-correspondent-contactInWelsh'])) {
            $this->flattenLpa['correspondent-contact-in-welsh'] = 'On';
        }
        
        
        // Payment section (section 14)
        if($this->flattenLpa['lpa-payment-method'] == Payment::PAYMENT_TYPE_CARD) {
            $this->flattenLpa['pay-by-card'] = 'On';
        }
        elseif($this->flattenLpa['lpa-payment-method'] == Payment::PAYMENT_TYPE_CHEQUE) {
            $this->flattenLpa['pay-by-cheque'] = 'On';
        }
        
        // @todo: Fee reduction, repeat application
        
        if(isset($this->flattenLpa['lpa-payment-reference'])) {
            $this->flattenLpa['lpa-payment-amount'] = '£'.sprintf('%.2f', $this->flattenLpa['lpa-payment-amount']);
            $this->flattenLpa['lpa-payment-date-day'] = $this->lpa->payment->date->format('d');
            $this->flattenLpa['lpa-payment-date-month'] = $this->lpa->payment->date->format('m');
            $this->flattenLpa['lpa-payment-date-year'] = $this->lpa->payment->date->format('Y');
        }
        
    }
} // class