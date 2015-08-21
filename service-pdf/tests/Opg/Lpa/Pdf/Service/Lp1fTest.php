<?php
namespace OpgTest\Lpa\Pdf\Service;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\Pdf\Service\Forms\Lp1;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\Pdf\Service\Forms\Cs1;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Elements\Address;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;

class Lp1fTest extends BaseClass
{

    public function testLP1()
    {
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        // test is coversheet for registration LPA
        $this->assertEquals('A'.$this->lpa->id.'.', str_replace(' ', '', $formData['lpa-number']));
        $this->assertArrayNotHasKey('lpa-type', $formData);
        
        // test footer
        $this->assertEquals(Config::getInstance()['footer']['lp1f']['instrument'], $formData['footer-instrument-right']);
        
        // test donor fields (section 1)
        $this->assertEquals($this->lpa->document->donor->name->title, $formData['lpa-document-donor-name-title']);
        $this->assertEquals($this->lpa->document->donor->name->first, $formData['lpa-document-donor-name-first']);
        $this->assertEquals($this->lpa->document->donor->name->last, $formData['lpa-document-donor-name-last']);
        $this->assertEquals($this->lpa->document->donor->otherNames, $formData['lpa-document-donor-otherNames']);
        $this->assertEquals($this->lpa->document->donor->dob->date->format('d'), $formData['lpa-document-donor-dob-date-day']);
        $this->assertEquals($this->lpa->document->donor->dob->date->format('m'), $formData['lpa-document-donor-dob-date-month']);
        $this->assertEquals($this->lpa->document->donor->dob->date->format('Y'), $formData['lpa-document-donor-dob-date-year']);
        $this->assertEquals($this->lpa->document->donor->address->address1, $formData['lpa-document-donor-address-address1']);
        $this->assertEquals($this->lpa->document->donor->address->address2, $formData['lpa-document-donor-address-address2']);
        $this->assertEquals($this->lpa->document->donor->address->address3, $formData['lpa-document-donor-address-address3']);
        $this->assertEquals($this->lpa->document->donor->address->postcode, $formData['lpa-document-donor-address-postcode']);
        
        if($this->lpa->document->donor->email instanceof EmailAddress) {
            $this->assertEquals($this->lpa->document->donor->email->address, $formData['lpa-document-donor-email-address']);
        }
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test primary attorneys fields (section 2)
        $trust = $this->getTrustCorp($this->lpa->document->primaryAttorneys);
        $pdfAttorneyIdx = 0;
        if($trust !== null) {
            // test trust name
            $this->assertEquals($trust->name, $formData['lpa-document-primaryAttorneys-0-name-last']);
            
            // checkbox for 'This attorney is a trust corporation'
            $this->assertEquals('On', $formData['attorney-0-is-trust-corporation']);
            $pdfAttorneyIdx++;
        }
        
        foreach($this->lpa->document->primaryAttorneys as $attorney) {

            // break the loop if number of attorney is greater than primary attorney forms on LP1.
            if($pdfAttorneyIdx == Lp1::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                $this->assertEquals('On', $formData['has-more-than-4-attorneys']);
                break;
            }
            
            // attorney address fields
            $this->assertEquals($attorney->address->address1, $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-address-address1']);
            $this->assertEquals($attorney->address->address2, $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-address-address2']);
            $this->assertEquals($attorney->address->address3, $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-address-address3']);
            $this->assertEquals($attorney->address->postcode, $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-address-postcode']);
            
            if($attorney->email instanceof EmailAddress) {
                $this->assertEquals($attorney->email->address, str_replace('&#10;','',$formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-email-address']));
            }
            
            // skip trust corporation for dob and name fields 
            if(($trust !== null) && ($attorney->id == $trust->id)) {
                continue;
            }
            
            // test attorney DOB
            $this->assertEquals($attorney->dob->date->format('d'), $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-dob-date-day']);
            $this->assertEquals($attorney->dob->date->format('m'), $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-dob-date-month']);
            $this->assertEquals($attorney->dob->date->format('Y'), $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-dob-date-year']);
            
            // test attorney name fields
            $this->assertEquals($attorney->name->title, $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-name-title']);
            $this->assertEquals($attorney->name->first, $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-name-first']);
            $this->assertEquals($attorney->name->last,  $formData['lpa-document-primaryAttorneys-'.$pdfAttorneyIdx.'-name-last']);
            
            $pdfAttorneyIdx++;
        }
        
        unset($trust, $pdfAttorneyIdx, $attorney);
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test replacement attorneys fields (section 4)
        $trust = $this->getTrustCorp($this->lpa->document->replacementAttorneys);
        $pdfAttorneyIdx = 0;
        if($trust !== null) {
            // test trust name
            $this->assertEquals($trust->name, $formData['lpa-document-replacementAttorneys-0-name-last']);
            
            // checkbox for 'This attorney is a trust corporation'
            $this->assertEquals('On', $formData['replacement-attorney-0-is-trust-corporation']);
            $pdfAttorneyIdx++;
        }
        
        foreach($this->lpa->document->replacementAttorneys as $attorney) {

            // break the loop if number of attorney is greater than primary attorney forms on LP1.
            if($pdfAttorneyIdx == Lp1::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM) {
                $this->assertEquals('On', $formData['has-more-than-2-replacement-attorneys']);
                break;
            }
            
            // attorney address fields
            $this->assertEquals($attorney->address->address1, $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-address-address1']);
            $this->assertEquals($attorney->address->address2, $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-address-address2']);
            $this->assertEquals($attorney->address->address3, $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-address-address3']);
            $this->assertEquals($attorney->address->postcode, $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-address-postcode']);
        
            // skip trust corporation for dob and name fields
            if(($trust !== null) && ($attorney->id == $trust->id)) {
                continue;
            }
        
            // test attorney DOB
            $this->assertEquals($attorney->dob->date->format('d'), $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-dob-date-day']);
            $this->assertEquals($attorney->dob->date->format('m'), $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-dob-date-month']);
            $this->assertEquals($attorney->dob->date->format('Y'), $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-dob-date-year']);
        
            // test attorney name fields
            $this->assertEquals($attorney->name->title, $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-name-title']);
            $this->assertEquals($attorney->name->first, $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-name-first']);
            $this->assertEquals($attorney->name->last,  $formData['lpa-document-replacementAttorneys-'.$pdfAttorneyIdx.'-name-last']);
        
            $pdfAttorneyIdx++;
        }
        
        unset($trust, $pdfAttorneyIdx, $attorney);
        
        // test when and how replacement attorneys can act 
        //@todo can be more generic
        $this->assertEquals('On', $formData['change-how-replacement-attorneys-step-in']);
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test people to notify (section 6)
        $pdfNotifiedPersonIdx = 0;
        foreach($this->lpa->document->peopleToNotify as $notifiedPerson) {
            
            // break the loop if number of attorney is greater than primary attorney forms on LP1.
            if($pdfNotifiedPersonIdx == Lp1::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
                $this->assertEquals('On', $formData['has-more-than-4-notified-people']);
                break;
            }
            
            // people to notify address fields
            $this->assertEquals($notifiedPerson->address->address1, $formData['lpa-document-peopleToNotify-'.$pdfNotifiedPersonIdx.'-address-address1']);
            $this->assertEquals($notifiedPerson->address->address2, $formData['lpa-document-peopleToNotify-'.$pdfNotifiedPersonIdx.'-address-address2']);
            $this->assertEquals($notifiedPerson->address->address3, $formData['lpa-document-peopleToNotify-'.$pdfNotifiedPersonIdx.'-address-address3']);
            $this->assertEquals($notifiedPerson->address->postcode, $formData['lpa-document-peopleToNotify-'.$pdfNotifiedPersonIdx.'-address-postcode']);
        
        
            // test people to notify name fields
            $this->assertEquals($notifiedPerson->name->title, $formData['lpa-document-peopleToNotify-'.$pdfNotifiedPersonIdx.'-name-title']);
            $this->assertEquals($notifiedPerson->name->first, $formData['lpa-document-peopleToNotify-'.$pdfNotifiedPersonIdx.'-name-first']);
            $this->assertEquals($notifiedPerson->name->last,  $formData['lpa-document-peopleToNotify-'.$pdfNotifiedPersonIdx.'-name-last']);
        
            $pdfNotifiedPersonIdx++;
        
        }
        
        unset($pdfNotifiedPersonIdx, $notifiedPerson);
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test certificate provider (section 10)
        $this->assertEquals($this->lpa->document->certificateProvider->name->title, $formData['lpa-document-certificateProvider-name-title']);
        $this->assertEquals($this->lpa->document->certificateProvider->name->first, $formData['lpa-document-certificateProvider-name-first']);
        $this->assertEquals($this->lpa->document->certificateProvider->name->last, $formData['lpa-document-certificateProvider-name-last']);
        $this->assertEquals($this->lpa->document->certificateProvider->address->address1, $formData['lpa-document-certificateProvider-address-address1']);
        $this->assertEquals($this->lpa->document->certificateProvider->address->address2, $formData['lpa-document-certificateProvider-address-address2']);
        $this->assertEquals($this->lpa->document->certificateProvider->address->address3, $formData['lpa-document-certificateProvider-address-address3']);
        $this->assertEquals($this->lpa->document->certificateProvider->address->postcode, $formData['lpa-document-certificateProvider-address-postcode']);
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test attorney/replacement signature (section 11)
        $attorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $idx = 0;
        foreach($attorneys as $attorney) {
            if($attorney instanceof TrustCorporation) continue;
            if($idx == LP1::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) break;
            $this->assertEquals($attorney->name->title, $formData['signature-attorney-'.$idx.'-name-title']);
            $this->assertEquals($attorney->name->first, $formData['signature-attorney-'.$idx.'-name-first']);
            $this->assertEquals($attorney->name->last, $formData['signature-attorney-'.$idx.'-name-last']);
            $idx++;
        }
        
        unset($idx, $attorneys, $attorney);
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test additional attorney/replacement signature (section 11 additional sheets)
        
        $autoIncrementNo = 0;
        $humanAttorneys = $this->getHumanAttorneys();
        
        if(count($humanAttorneys) > LP1::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) {
            for($i=0; $i<LP1::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM; $i++) array_shift($humanAttorneys);
            
            foreach($humanAttorneys as $attorney) {
                
                $prefixIdx = $autoIncrementNo?($autoIncrementNo.'.'):'';
                
                $this->assertEquals($attorney->name->title, $formData[$prefixIdx.'signature-attorney-name-title']);
                $this->assertEquals($attorney->name->first, $formData[$prefixIdx.'signature-attorney-name-first']);
                $this->assertEquals($attorney->name->last, $formData[$prefixIdx.'signature-attorney-name-last']);
                $this->assertEquals(Config::getInstance()['footer']['lp1f']['instrument'], $formData[$prefixIdx.'footer-instrument-right-additional']);
                $autoIncrementNo++;
            }
            
        }
        
        unset($humanAttorneys, $attorney, $prefixIdx);

        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test continuation sheet 1
        
        $cs2Persons = $this->getAdditionalPeopleForCS1();
        $needMoreCs1 = false;
        $personNumber=0;
        $extraCs1Pages = 0;
        
        foreach($cs2Persons as $type=>$persons) {
            foreach($persons as $person) {
                if($needMoreCs1) {
                    $prefixIdx = $autoIncrementNo . '.';
                }
                else {
                    $prefixIdx = '';
                }
                
                $personIdx = ($personNumber % Cs1::$SETTINGS['max-slots-on-cs1-form']);
                $this->assertEquals($type, $formData[$prefixIdx.'cs1-'.$personIdx.'-is']);
                $this->assertEquals($person->name->title, $formData[$prefixIdx.'cs1-'.$personIdx.'-name-title']);
                $this->assertEquals($person->name->first, $formData[$prefixIdx.'cs1-'.$personIdx.'-name-first']);
                $this->assertEquals($person->name->last, $formData[$prefixIdx.'cs1-'.$personIdx.'-name-last']);
                $this->assertEquals($person->address->address1, $formData[$prefixIdx.'cs1-'.$personIdx.'-address-address1']);
                $this->assertEquals($person->address->address2, $formData[$prefixIdx.'cs1-'.$personIdx.'-address-address2']);
                $this->assertEquals($person->address->address3, $formData[$prefixIdx.'cs1-'.$personIdx.'-address-address3']);
                $this->assertEquals($person->address->postcode, $formData[$prefixIdx.'cs1-'.$personIdx.'-address-postcode']);
                
                $this->assertEquals((string)$this->lpa->document->donor->name, $formData[$prefixIdx.'cs1-donor-full-name']);
                $this->assertEquals(Config::getInstance()['footer']['cs1'], $formData[$prefixIdx.'cs1-footer-right']);
                
                if($type != "peopleToNotify") {
                    
                    $this->assertEquals($person->dob->date->format('d'), $formData[$prefixIdx.'cs1-'.$personIdx.'-dob-date-day']);
                    $this->assertEquals($person->dob->date->format('m'), $formData[$prefixIdx.'cs1-'.$personIdx.'-dob-date-month']);
                    $this->assertEquals($person->dob->date->format('Y'), $formData[$prefixIdx.'cs1-'.$personIdx.'-dob-date-year']);
                    
                    if($type != 'replacementAttorney') {
                        $this->assertEquals($person->email->address, str_replace('&#10;','',$formData[$prefixIdx.'cs1-'.$personIdx.'-email-address']));
                    }
                }
                
                $personNumber++;
                if($personNumber % Cs1::$SETTINGS['max-slots-on-cs1-form'] == 0) {
                    $extraCs1Pages++;
                    $needMoreCs1 = true;
                    
                    if($extraCs1Pages > 1) {
                        $autoIncrementNo++;
                    }
                }
                
            }
        }
        
        unset($cs2Persons, $type, $person, $persons, $needMoreCs1, $personNumber, $extraCs1Pages, $prefixIdx);
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test continuation sheet 2
        //@todo can be more generic
        $this->assertEquals('how-replacement-attorneys-step-in', $formData['cs2-is']);
        $this->assertEquals($this->lpa->document->donor->name, $formData['cs2-donor-full-name']);
        $this->assertEquals(Config::getInstance()['footer']['cs2'], $formData['cs2-footer-right']);
        $this->assertEquals('', $formData['cs2-continued']);
        $this->assertTrue(strstr($formData['cs2-content'], 'Replacement attorneys to step in only when none of the original attorneys can act') !== false);
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test continuation sheet 3
        $this->assertEquals($this->lpa->document->donor->name, $formData['cs3-donor-full-name']);
        $this->assertEquals(Config::getInstance()['footer']['cs3'], $formData['cs3-footer-right']);
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test continuation sheet 4
        $trust = $this->getTrustCorp($this->lpa->document->primaryAttorneys);
        $this->assertEquals($trust->number, $formData['cs4-trust-corporation-company-registration-number']);
        $this->assertEquals(Config::getInstance()['footer']['cs4'], $formData['cs4-footer-right']);
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test footers for registration pages 
        $this->assertEquals(Config::getInstance()['footer']['lp1f']['registration'], $formData['footer-registration-right']);
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test applicant (section 12)
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $this->assertEquals('donor', $formData['who-is-applicant']);
        }
        else {
            $this->assertEquals('attorney', $formData['who-is-applicant']);
            $idx = 0;
            $extraCs2Pages = 0;
            $prefixIdx='';
            foreach($this->lpa->document->whoIsRegistering as $attorenyId) {
                $attorney = $this->lpa->document->getPrimaryAttorneyById($attorenyId);
                if($attorney instanceof Human) {
                    $this->assertEquals($attorney->name->title, $formData[$prefixIdx.'applicant-'.($idx % LP1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM).'-name-title']);
                    $this->assertEquals($attorney->name->first, $formData[$prefixIdx.'applicant-'.($idx % LP1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM).'-name-first']);
                    $this->assertEquals($attorney->dob->date->format('d'), $formData[$prefixIdx.'applicant-'.($idx % LP1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM).'-dob-date-day']);
                    $this->assertEquals($attorney->dob->date->format('m'), $formData[$prefixIdx.'applicant-'.($idx % LP1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM).'-dob-date-month']);
                    $this->assertEquals($attorney->dob->date->format('Y'), $formData[$prefixIdx.'applicant-'.($idx % LP1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM).'-dob-date-year']);
                    $this->assertEquals($attorney->name->last, $formData[$prefixIdx.'applicant-'.($idx % LP1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM).'-name-last']);
                }
                else {
                    $this->assertEquals($attorney->name, $formData[$prefixIdx.'applicant-'.($idx % LP1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM).'-name-last']);
                }
                $idx++;
                if($idx % LP1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM == 0) {
                    $extraCs2Pages++;
                    if($extraCs2Pages > 0) {
                        $prefixIdx = $autoIncrementNo++ . '.';
                    }
                }
            }
        }
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test (section 14)
        if($this->lpa->payment instanceof Payment) {
            
            if($this->lpa->payment->method) {
                $this->assertEquals($this->lpa->payment->method, $formData['pay-by']);
            }
            
            if($this->lpa->payment->method == Payment::PAYMENT_TYPE_CARD) {
                $this->assertEquals('NOT REQUIRED. PAYMENT MADE ONLINE.', $formData['lpa-payment-phone-number']);
                $this->assertEquals($this->lpa->payment->reference, $formData['lpa-payment-reference']);
                $this->assertEquals($this->lpa->payment->date->format('d'), $formData['lpa-payment-date-day']);
                $this->assertEquals($this->lpa->payment->date->format('m'), $formData['lpa-payment-date-month']);
                $this->assertEquals($this->lpa->payment->date->format('Y'), $formData['lpa-payment-date-year']);
                $this->assertEquals($this->lpa->payment->amount, $formData['lpa-payment-amount']);
            }
            
            if(($this->lpa->payment->reducedFeeReceivesBenefits && $this->lpa->payment->reducedFeeAwardedDamages) ||
                    $this->lpa->payment->reducedFeeLowIncome ||
                    $this->lpa->payment->reducedFeeUniversalCredit) {
            
                        $this->assertEquals('On', $formData['apply-for-fee-reduction']);
                    }
            
            if(!empty($this->lpa->repeatCaseNumber)) {
                $this->assertEquals('On', $formData['is-repeat-application']);
                $this->assertEquals($this->lpa->repeatCaseNumber, $formData['repeat-application-case-number']);
            }
        }
        
        
        /*\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\*/
        
        // test (section 15)
        if(is_array($this->lpa->document->whoIsRegistering) && 
                (count($this->lpa->document->whoIsRegistering) > Lp1::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM)) {
            for($i=0; $i<ceil((count($this->lpa->document->whoIsRegistering) - Lp1::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM)/Lp1::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM); $i++) {
                $prefix = ($i+$autoIncrementNo).'.';
            }
            $this->assertEquals(Config::getInstance()['footer']['lp1f']['registration'], $formData[$prefix.'footer-registration-right-additional']);
        }
    }
    
    public function testCoversheetIsForInstrument()
    {
        // unset payment to force generate LPA instrument
        $this->lpa->payment = null;
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        // test is coversheet for registration LPA
        $this->assertEquals('A'.$this->lpa->id.'.', str_replace(' ', '', $formData['lpa-number']));
        $this->assertEquals(($this->lpa->document->type=='property-and-financial')?'property and financial affairs.':'health and welfare.', $formData['lpa-type']);
    }
    
    /**
     * test Section 3
     */
    public function testAttorneysActJointlySeveraly()
    {
        if(count($this->lpa->document->primaryAttorneys) < 2) return;
        
        // set attorneys make decisions jointly
        $this->lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY;
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals(PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY, $formData['how-attorneys-act']);
    }

    /**
     * test Section 3
     */
    public function testAttorneysActJointly()
    {
        if(count($this->lpa->document->primaryAttorneys) < 2) return;
    
        // set attorneys make decisions jointly
        $this->lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;
    
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
    
        $this->assertEquals(PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY, $formData['how-attorneys-act']);
    }
    
    /**
     * test Section 3
     */
    public function testAttorneysActDependsOnDecisions()
    {
        if(count($this->lpa->document->primaryAttorneys) < 2) return;
        
        // set attorneys make decisions depends
        $this->lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS;
        $this->lpa->document->primaryAttorneyDecisions->howDetails = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit';
    
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals(PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS, $formData['how-attorneys-act']);
        $this->assertEquals('Lorem ipsum dolor sit amet, consectetur adipiscing elit', str_replace('&#10;', '', trim($formData['cs2-content'])));
    }

    /**
     * test Section 3
     */
    public function testOnlyOneAttorneyAppointed()
    {
        // set only one attorney appointed
        $this->lpa->document->primaryAttorneys = [$this->lpa->document->primaryAttorneys[0]];
        $this->lpa->document->primaryAttorneyDecisions->how = null;
        $this->lpa->document->primaryAttorneyDecisions->howDetails = null;
        $this->lpa->document->whoIsRegistering = [1];
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals('only-one-attorney-appointed', $formData['how-attorneys-act']);
        $this->assertArrayNotHasKey('attorney-0-is-trust-corporation', $formData);
        $this->assertArrayNotHasKey('has-more-than-4-attorneys', $formData);
        
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->name->title, $formData['lpa-document-primaryAttorneys-0-name-title']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->name->first, $formData['lpa-document-primaryAttorneys-0-name-first']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->name->last, $formData['lpa-document-primaryAttorneys-0-name-last']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->dob->date->format('Y'), $formData['lpa-document-primaryAttorneys-0-dob-date-year']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->dob->date->format('m'), $formData['lpa-document-primaryAttorneys-0-dob-date-month']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->dob->date->format('d'), $formData['lpa-document-primaryAttorneys-0-dob-date-day']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->address->address1, $formData['lpa-document-primaryAttorneys-0-address-address1']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->address->address2, $formData['lpa-document-primaryAttorneys-0-address-address2']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->address->address3, $formData['lpa-document-primaryAttorneys-0-address-address3']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->address->postcode, $formData['lpa-document-primaryAttorneys-0-address-postcode']);
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->email->address, str_replace('&#10;', '', $formData['lpa-document-primaryAttorneys-0-email-address']));
        
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-name-title', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-name-first', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-name-last', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-dob-date-year', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-dob-date-month', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-dob-date-day', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-email-address', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-1-address-postcode', $formData);

        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-name-title', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-name-first', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-name-last', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-dob-date-year', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-dob-date-month', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-dob-date-day', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-email-address', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-2-address-postcode', $formData);

        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-name-title', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-name-first', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-name-last', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-dob-date-year', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-dob-date-month', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-dob-date-day', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-email-address', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-primaryAttorneys-3-address-postcode', $formData);
        
    }
    
    /**
     * Test section 4
     */
    public function testReplacementAttorneysFollowDefaultArrangement()
    {
        $this->lpa->document->replacementAttorneyDecisions->when = 'first';
        $this->lpa->document->replacementAttorneyDecisions->how = "jointly-attorney-severally";
        $this->lpa->document->replacementAttorneyDecisions->howDetails = null;
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertArrayNotHasKey('change-how-replacement-attorneys-step-in', $formData);
    }
    
    /**
     * Test section 4
     */
    public function testLpaHasOneReplacementAttorney()
    {
        $this->lpa->document->replacementAttorneys = [$this->lpa->document->replacementAttorneys[0]];
        $this->lpa->document->replacementAttorneyDecisions->when = 'first';
        $this->lpa->document->replacementAttorneyDecisions->how = null;
        $this->lpa->document->replacementAttorneyDecisions->howDetails = null;
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertArrayNotHasKey('has-more-than-2-replacement-attorneys', $formData);

        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->name->title, $formData['lpa-document-replacementAttorneys-0-name-title']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->name->first, $formData['lpa-document-replacementAttorneys-0-name-first']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->name->last, $formData['lpa-document-replacementAttorneys-0-name-last']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->dob->date->format('Y'), $formData['lpa-document-replacementAttorneys-0-dob-date-year']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->dob->date->format('m'), $formData['lpa-document-replacementAttorneys-0-dob-date-month']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->dob->date->format('d'), $formData['lpa-document-replacementAttorneys-0-dob-date-day']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->address->address1, $formData['lpa-document-replacementAttorneys-0-address-address1']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->address->address2, $formData['lpa-document-replacementAttorneys-0-address-address2']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->address->address3, $formData['lpa-document-replacementAttorneys-0-address-address3']);
        $this->assertEquals($this->lpa->document->replacementAttorneys[0]->address->postcode, $formData['lpa-document-replacementAttorneys-0-address-postcode']);
        
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-name-title', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-name-first', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-name-last', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-dob-date-year', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-dob-date-month', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-dob-date-day', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-replacementAttorneys-1-address-postcode', $formData);
        
    }
    
    /**
     * Test section 4
     */
    public function testTrustCorpIsReplacementAttorney()
    {
        $trustCorp = $this->getTrustCorp($this->lpa->document->primaryAttorneys);
        $this->deleteTrustCorp();
        $this->lpa->document->replacementAttorneys[] = $trustCorp;
        $this->lpa->document->whoIsRegistering = 'donor';
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertArrayNotHasKey('attorney-0-is-trust-corporation', $formData);
        $this->assertEquals('On', $formData['replacement-attorney-0-is-trust-corporation']);
    }
    
    /**
     * Test section 5
     */
    public function testAttorneyCanMakeDecisionWhenDonorHasNoMentalCapacity()
    {
        $this->lpa->document->primaryAttorneyDecisions->when = PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY;
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals('when-donor-lost-mental-capacity', $formData['when-attorneys-may-make-decisions']);
        
    }

    /**
     * Test section 5
     */
    public function testAttorneyCanMakeDecisionRightNow()
    {
        $this->lpa->document->primaryAttorneyDecisions->when = PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW;
    
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
    
        $this->assertEquals('when-lpa-registered', $formData['when-attorneys-may-make-decisions']);
    
    }
    
    /**
     * Test section 6
     */
    public function testLpaHasOnePeopleToNotify()
    {
        $this->lpa->document->peopleToNotify = [$this->lpa->document->peopleToNotify[0]];
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals($this->lpa->document->peopleToNotify[0]->name->title, $formData['lpa-document-peopleToNotify-0-name-title']);
        $this->assertEquals($this->lpa->document->peopleToNotify[0]->name->first, $formData['lpa-document-peopleToNotify-0-name-first']);
        $this->assertEquals($this->lpa->document->peopleToNotify[0]->name->last, $formData['lpa-document-peopleToNotify-0-name-last']);
        $this->assertEquals($this->lpa->document->peopleToNotify[0]->address->address1, $formData['lpa-document-peopleToNotify-0-address-address1']);
        $this->assertEquals($this->lpa->document->peopleToNotify[0]->address->address2, $formData['lpa-document-peopleToNotify-0-address-address2']);
        $this->assertEquals($this->lpa->document->peopleToNotify[0]->address->address3, $formData['lpa-document-peopleToNotify-0-address-address3']);
        $this->assertEquals($this->lpa->document->peopleToNotify[0]->address->postcode, $formData['lpa-document-peopleToNotify-0-address-postcode']);
        
        $this->assertArrayNotHasKey('has-more-than-4-notified-people', $formData);
        
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-1-name-title', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-1-name-first', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-1-name-last', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-1-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-1-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-1-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-1-address-postcode', $formData);
        
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-2-name-title', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-2-name-first', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-2-name-last', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-2-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-2-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-2-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-2-address-postcode', $formData);
        
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-3-name-title', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-3-name-first', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-3-name-last', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-3-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-3-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-3-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-peopleToNotify-3-address-postcode', $formData);
    }
    
    /**
     * Test section 7
     */
    public function testPreferenceAndInstruction()
    {
        $this->lpa->document->preference = "Maecenas posuere augue sed purus malesuada dapibus.";
        $this->lpa->document->instruction = "Lorem ipsum dolor sit amet, consectetur adipiscing elit.";
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals($this->lpa->document->preference, trim(str_replace('&#10;','',$formData['lpa-document-preference'])));
        $this->assertEquals($this->lpa->document->instruction, trim(str_replace('&#10;','',$formData['lpa-document-instruction'])));
        $this->assertArrayNotHasKey('has-more-preferences', $formData);
        $this->assertArrayNotHasKey('has-more-instructions', $formData);
    }

    /**
     * Test section 7
     */
    public function testPreferenceAndInstructionNeedMoreSpace()
    {
        $this->lpa->document->instruction = str_repeat(implode(' ', range('a','z'))."\r\n", 20);
        $this->lpa->document->preference = str_repeat(implode(' ', range('a','z'))."\r\n", 20);
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals('On', $formData['has-more-instructions']);
        $this->assertEquals('On', $formData['has-more-preferences']);
    }

    /**
     * Test section 9
     */
    public function testDonorCanSign()
    {
        $this->lpa->document->donor->canSign = true;
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertArrayNotHasKey('see_continuation_sheet_3', $formData);
    }
    
    /**
     * Test section 9
     */
    public function testDonorCanNotSign()
    {
        $this->lpa->document->donor->canSign = false;
    
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
    
        $this->assertEquals('see continuation sheet 3', $formData['see_continuation_sheet_3']);
    }
    
    /**
     * Test section 13
     */
    public function testCorrespondentIsDonorAndContactByEmailAndPhone()
    {
        $this->lpa->document->correspondent = new Correspondence([
                'who' => 'donor',
                'email' => new EmailAddress(['address'=>'test@mail.net']),
                'phone' => new PhoneNumber(['number'=>'012345678']),
        ]);
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals('donor', $formData['who-is-correspondent']);
        $this->assertArrayNotHasKey('lpa-document-correspondent-name-title', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-name-first', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-name-last', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-company', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-address-postcode', $formData);
        
        $this->assertEquals($this->lpa->document->correspondent->email->address, $formData['lpa-document-correspondent-email-address']);
        $this->assertEquals('On', $formData['correspondent-contact-by-email']);
        
        $this->assertEquals($this->lpa->document->correspondent->phone->number, $formData['lpa-document-correspondent-phone-number']);
        $this->assertEquals('On', $formData['correspondent-contact-by-phone']);
        
        $this->assertArrayNotHasKey('correspondent-contact-by-post', $formData);
        $this->assertArrayNotHasKey('correspondent-contact-in-welsh', $formData);
        
    }

    /**
     * Test section 13
     */
    public function testCorrespondentIsAttorneyAndContactByPostAndInWelsh()
    {
        $this->lpa->document->correspondent = new Correspondence([
                'who' => 'attorney',
                'name' => new Name(['title'=>'Mr','first'=>'Cindy', 'last'=>'Clark']),
                'address' => new Address(['address1'=>'123 Brook Street','address2'=>'Purley', 'postcode'=>'CR1 4AQ']),
                'contactByPost' => true,
                'contactInWelsh' => true
        ]);
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        
        $this->assertEquals('attorney', $formData['who-is-correspondent']);
        $this->assertEquals($this->lpa->document->correspondent->name->title, $formData['lpa-document-correspondent-name-title']);
        $this->assertEquals($this->lpa->document->correspondent->name->first, $formData['lpa-document-correspondent-name-first']);
        $this->assertEquals($this->lpa->document->correspondent->name->last, $formData['lpa-document-correspondent-name-last']);
        $this->assertEquals(null, $formData['lpa-document-correspondent-company']);
        $this->assertArrayNotHasKey('lpa-document-correspondent-address-address1', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-address-address2', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-address-address3', $formData);
        $this->assertArrayNotHasKey('lpa-document-correspondent-address-postcode', $formData);
        
        $this->assertArrayNotHasKey('lpa-document-correspondent-email-address', $formData);
        $this->assertArrayNotHasKey('correspondent-contact-by-email', $formData);
    
        $this->assertArrayNotHasKey('lpa-document-correspondent-phone-number', $formData);
        $this->assertArrayNotHasKey('correspondent-contact-by-phone', $formData);
    
        $this->assertEquals('On', $formData['correspondent-contact-by-post']);
        $this->assertEquals('On', $formData['correspondent-contact-in-welsh']);
    
    }
    
    /**
     * Test section 13
     */
    public function testCorrespondentIsOther()
    {
        $this->lpa->document->correspondent = new Correspondence([
                'who' => 'other',
                'name' => new Name(['title'=>'Mr','first'=>'Cindy', 'last'=>'Clark']),
                'company' => 'Trust Corp',
                'address' => new Address(['address1'=>'123 Brook Street','address2'=>'Purley', 'postcode'=>'CR1 4AQ']),
                'email' => new EmailAddress(['address'=>'email@email.net']),
                'phone' => new PhoneNumber(['number'=>'012345678']),
                'contactByPost' => true,
        ]);
    
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertEquals('other', $formData['who-is-correspondent']);
        $this->assertEquals($this->lpa->document->correspondent->name->title, $formData['lpa-document-correspondent-name-title']);
        $this->assertEquals($this->lpa->document->correspondent->name->first, $formData['lpa-document-correspondent-name-first']);
        $this->assertEquals($this->lpa->document->correspondent->name->last, $formData['lpa-document-correspondent-name-last']);
        $this->assertEquals($this->lpa->document->correspondent->company, $formData['lpa-document-correspondent-company']);
    
        $this->assertEquals($this->lpa->document->correspondent->address->address1, $formData['lpa-document-correspondent-address-address1']);
        $this->assertEquals($this->lpa->document->correspondent->address->address2, $formData['lpa-document-correspondent-address-address2']);
        $this->assertEquals($this->lpa->document->correspondent->address->address3, $formData['lpa-document-correspondent-address-address3']);
        $this->assertEquals($this->lpa->document->correspondent->address->postcode, $formData['lpa-document-correspondent-address-postcode']);
    
        $this->assertEquals($this->lpa->document->correspondent->email->address, $formData['lpa-document-correspondent-email-address']);
        $this->assertEquals('On', $formData['correspondent-contact-by-email']);
        
        $this->assertEquals($this->lpa->document->correspondent->phone->number, $formData['lpa-document-correspondent-phone-number']);
        $this->assertEquals('On', $formData['correspondent-contact-by-phone']);
        
        $this->assertEquals('On', $formData['correspondent-contact-by-post']);
        $this->assertArrayNotHasKey('correspondent-contact-in-welsh', $formData);
    
    }
    
    /**
     * Test section 14
     */
    public function testPayByCardForRepeatApplication()
    {
        $today = new \DateTime();
        $this->lpa->payment->reducedFeeReceivesBenefits=false;
        $this->lpa->payment->reducedFeeAwardedDamages=null;
        $this->lpa->payment->reducedFeeLowIncome = true;
        $this->lpa->payment->reducedFeeUniversalCredit = null;
        $this->lpa->payment->amount = 27.5;
        $this->lpa->payment->method = 'card';
        $this->lpa->payment->date = $today;
        $this->lpa->payment->reference = '12345678';
        $this->lpa->repeatCaseNumber = '98765432';
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        $this->assertEquals('card', $formData['pay-by']);
        $this->assertEquals('NOT REQUIRED.', $formData['lpa-payment-phone-number']);
        $this->assertEquals('On', $formData['is-repeat-application']);
        $this->assertEquals('98765432', $formData['repeat-application-case-number']);
        $this->assertEquals('12345678', $formData['lpa-payment-reference']);
        $this->assertEquals("£27.50", html_entity_decode($formData['lpa-payment-amount']));
        $this->assertEquals($today->format('d'), $formData['lpa-payment-date-day']);
        $this->assertEquals($today->format('m'), $formData['lpa-payment-date-month']);
        $this->assertEquals($today->format('Y'), $formData['lpa-payment-date-year']);
    }

    /**
     * Test section 14
     */
    public function testPayByCheque()
    {
        $today = new \DateTime();
        $this->lpa->payment->reducedFeeReceivesBenefits=false;
        $this->lpa->payment->reducedFeeAwardedDamages=null;
        $this->lpa->payment->reducedFeeLowIncome = true;
        $this->lpa->payment->reducedFeeUniversalCredit = null;
        $this->lpa->payment->amount = 55;
        $this->lpa->payment->method = 'cheque';
        $this->lpa->payment->date = $today;
    
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        $this->assertEquals('cheque', $formData['pay-by']);
        $this->assertArrayNotHasKey('lpa-payment-phone-number', $formData);
        $this->assertArrayNotHasKey('lpa-payment-reference', $formData);
        $this->assertArrayNotHasKey('lpa-payment-date-day', $formData);
        $this->assertArrayNotHasKey('lpa-payment-date-month', $formData);
        $this->assertArrayNotHasKey('lpa-payment-date-year', $formData);
    }

    /**
     * Test section 14
     */
    public function testExemption()
    {
        $today = new \DateTime();
        $this->lpa->payment->reducedFeeReceivesBenefits=true;
        $this->lpa->payment->reducedFeeAwardedDamages=true;
        $this->lpa->payment->reducedFeeLowIncome = null;
        $this->lpa->payment->reducedFeeUniversalCredit = null;
        $this->lpa->payment->amount = 0;
        $this->lpa->payment->method = null;
        $this->lpa->payment->date = null;
    
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        $this->assertArrayNotHasKey('pay-by', $formData);
        $this->assertArrayNotHasKey('lpa-payment-phone-number', $formData);
        $this->assertArrayNotHasKey('lpa-payment-reference', $formData);
        $this->assertArrayNotHasKey('lpa-payment-date-day', $formData);
        $this->assertArrayNotHasKey('lpa-payment-date-month', $formData);
        $this->assertArrayNotHasKey('lpa-payment-date-year', $formData);
    }
    
}