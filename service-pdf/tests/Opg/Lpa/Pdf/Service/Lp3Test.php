<?php
namespace OpgTest\Lpa\Pdf\Service;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\Pdf\Service\Forms\Lp3;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
class Lp3Test extends BaseClass
{
    public function test()
    {
        //  Unit tests do not execute without pdftk installed to container - the code needs to be restructured to allow mocking
        $this->markTestSkipped();

        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP3');

        $autoIncrementNo = 0;
        $notifiedPersonId = 0;
        $prefix = "";
        foreach($this->lpa->document->peopleToNotify as $notifiedPerson) {

            ################### page 1 #############
            if($notifiedPersonId > 0) {
                $autoIncrementNo++;
                $prefix = $autoIncrementNo.'.';
            }

            // test footer
            $this->assertEquals(Config::getInstance()['footer']['lp3'], $formData[$prefix.'footer-right-page-one']);

            $this->assertEquals($notifiedPerson->name->first, $formData[$prefix.'lpa-document-peopleToNotify-name-first']);
            $this->assertEquals($notifiedPerson->name->last, $formData[$prefix.'lpa-document-peopleToNotify-name-last']);
            $this->assertEquals($notifiedPerson->name->title, $formData[$prefix.'lpa-document-peopleToNotify-name-title']);

            $this->assertEquals($notifiedPerson->address->address1, $formData[$prefix.'lpa-document-peopleToNotify-address-address1']);
            $this->assertEquals($notifiedPerson->address->address2, $formData[$prefix.'lpa-document-peopleToNotify-address-address2']);
            $this->assertEquals($notifiedPerson->address->address3, $formData[$prefix.'lpa-document-peopleToNotify-address-address3']);
            $this->assertEquals($notifiedPerson->address->postcode, $formData[$prefix.'lpa-document-peopleToNotify-address-postcode']);


            ################### page 2 ##############

            if($notifiedPersonId > 0) {
                $autoIncrementNo++;
                $prefix = $autoIncrementNo.'.';
            }

            $this->assertEquals(Config::getInstance()['footer']['lp3'], $formData[$prefix.'footer-right-page-two']);

            $this->assertEquals($this->lpa->document->donor->name->first, $formData[$prefix.'lpa-document-donor-name-first']);
            $this->assertEquals($this->lpa->document->donor->name->last, $formData[$prefix.'lpa-document-donor-name-last']);
            $this->assertEquals($this->lpa->document->donor->name->title, $formData[$prefix.'lpa-document-donor-name-title']);
            $this->assertEquals($this->lpa->document->donor->address->address1, $formData[$prefix.'lpa-document-donor-address-address1']);
            $this->assertEquals($this->lpa->document->donor->address->address2, $formData[$prefix.'lpa-document-donor-address-address2']);
            $this->assertEquals($this->lpa->document->donor->address->address3, $formData[$prefix.'lpa-document-donor-address-address3']);
            $this->assertEquals($this->lpa->document->donor->address->postcode, $formData[$prefix.'lpa-document-donor-address-postcode']);

            if($this->lpa->document->whoIsRegistering == 'donor') {
                $this->assertEquals('donor', $formData[$prefix.'who-is-applicant']);
            }
            else {
                $this->assertEquals('attorney', $formData[$prefix.'who-is-applicant']);
            }

            if($this->lpa->document->type == Document::LPA_TYPE_PF) {
                $this->assertEquals('property-and-financial-affairs', $formData['lpa-type']);
            }
            elseif($this->lpa->document->type == Document::LPA_TYPE_HW) {
                $this->assertEquals('health-and-welfare', $formData['lpa-type']);
            }

            ################### page 3 #############

            if($notifiedPersonId > 0) {
                $autoIncrementNo++;
                $prefix = $autoIncrementNo.'.';
            }

            $this->assertEquals(Config::getInstance()['footer']['lp3'], $formData[$prefix.'footer-right-page-three']);

            if(count($this->lpa->document->primaryAttorneys) == 1) {
                $this->assertEquals('only-one-attorney-appointed', $formData[$prefix.'how-attorneys-act']);
            }
            else {
                $this->assertEquals($this->lpa->document->primaryAttorneyDecisions->how, $formData[$prefix.'how-attorneys-act']);
            }

            $personNumber=0;
            foreach($this->lpa->document->primaryAttorneys as $attorney) {

                $personIdx = ($personNumber % Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM);

                if(($personNumber>0) && (($personNumber % Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM) == 0)) {
                    $autoIncrementNo++;
                    $prefix = $autoIncrementNo.'.';
                    $this->assertEquals(Config::getInstance()['footer']['lp3'], $formData[$prefix.'footer-right-page-three']);
                }

                if($attorney instanceof Human) {
                    $this->assertEquals($attorney->name->first, $formData[$prefix.'lpa-document-primaryAttorneys-'.$personIdx.'-name-first']);
                    $this->assertEquals($attorney->name->last, $formData[$prefix.'lpa-document-primaryAttorneys-'.$personIdx.'-name-last']);
                    $this->assertEquals($attorney->name->title, $formData[$prefix.'lpa-document-primaryAttorneys-'.$personIdx.'-name-title']);
                    $this->assertEquals($attorney->address->address1, $formData[$prefix.'lpa-document-primaryAttorneys-'.$personIdx.'-address-address1']);
                    $this->assertEquals($attorney->address->address2, $formData[$prefix.'lpa-document-primaryAttorneys-'.$personIdx.'-address-address2']);
                    $this->assertEquals($attorney->address->address3, $formData[$prefix.'lpa-document-primaryAttorneys-'.$personIdx.'-address-address3']);
                    $this->assertEquals($attorney->address->postcode, $formData[$prefix.'lpa-document-primaryAttorneys-'.$personIdx.'-address-postcode']);
                }
                else {
                    $this->assertEquals($attorney->name, $formData[$prefix.'lpa-document-primaryAttorneys-'.$personIdx.'-name-last']);
                }

                $personNumber++;
            }

            ################### page 4 #############

            if($notifiedPersonId > 0) {
                $autoIncrementNo++;
                $prefix = $autoIncrementNo.'.';
            }
            else {
                $prefix = '';
            }

            $this->assertEquals(Config::getInstance()['footer']['lp3'], $formData[$prefix.'footer-right-page-four']);

            $notifiedPersonId++;
        }
    }
}