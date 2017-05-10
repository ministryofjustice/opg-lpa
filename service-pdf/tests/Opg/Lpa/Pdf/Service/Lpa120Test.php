<?php
namespace OpgTest\Lpa\Pdf\Service;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Elements\Dob;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;

class Lpa120Test extends BaseClass
{
    public function testBasicInformation()
    {
        //  Unit tests do not execute without pdftk installed to container - the code needs to be restructured to allow mocking
        $this->markTestSkipped();

        $this->lpa->repeatCaseNumber = null;

        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LPA120');

        $this->assertEquals((string)$this->lpa->document->donor->name, $formData['donor-full-name']);
        $this->assertEquals((string)$this->lpa->document->donor->address, str_replace('&#10;', '', $formData['donor-address']));
        $this->assertEquals('On', $formData['is-lpa']);
        $this->assertEquals(null, $formData['is-repeat-application']);

        if($this->lpa->document->type == Document::LPA_TYPE_PF) {
            $this->assertEquals('property-and-financial-affairs', $formData['lpa-type']);
        }
        else {
            $this->assertEquals('health-and-welfare', $formData['lpa-type']);
        }

        $this->assertEquals(($this->lpa->document->whoIsRegistering=='donor')?'donor':'attorney', $formData['applicant-type']);

        if($this->lpa->document->whoIsRegistering == 'donor') {
            $applicant = $this->lpa->document->donor;
        }
        else {
            $applicant = $this->lpa->document->getPrimaryAttorneyById($this->lpa->document->whoIsRegistering[0]);
        }

        if($applicant->name instanceof Name) {
            if(in_array(strtolower($applicant->name->title), ['mr','mrs','miss','ms'])) {
                $this->assertEquals(strtolower($applicant->name->title), $formData['applicant-name-title']);
            }
            else {
                $this->assertEquals('other', $formData['applicant-name-title']);
                $this->assertEquals($applicant->name->title, $formData['applicant-name-title-other']);
            }

            $this->assertEquals($applicant->name->first, $formData['applicant-name-first']);
            $this->assertEquals($applicant->name->last, $formData['applicant-name-last']);
        }
        else {
            $this->assertEquals($applicant->name, $formData['applicant-name-last']);
        }

        $this->assertEquals($applicant->address, str_replace('&#10;','',$formData['applicant-address']));

        if(property_exists($applicant, 'email') && ($applicant->email instanceof EmailAddress)) {
            $this->assertEquals($applicant->email, $formData['applicant-email-address']);
        }

    }

    public function testCaseNumber()
    {
        //  Unit tests do not execute without pdftk installed to container - the code needs to be restructured to allow mocking
        $this->markTestSkipped();

        $this->lpa->repeatCaseNumber = '12345678';

        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LPA120');

        $this->assertEquals((string)$this->lpa->repeatCaseNumber, $formData['case-number']);
        $this->assertEquals('On', $formData['is-repeat-application']);
    }

    public function testLpaType()
    {
        //  Unit tests do not execute without pdftk installed to container - the code needs to be restructured to allow mocking
        $this->markTestSkipped();

        if($this->lpa->document->type == Document::LPA_TYPE_PF) {
            $this->lpa->document->type = 'health-and-welfare';
            $this->lpa->document->primaryAttorneys = [new Human(['id'=>1, 'name'=>['title'=>'Mr','first'=>'Leon','last'=>'Carter'], 'address'=>['address1'=>'123 high street', 'postcode'=>'AB12 3CD'], 'dob'=>new Dob(['date'=> new \DateTime('1955-12-03')])])];
            $this->lpa->document->whoIsRegistering = 'donor';
        }
        else {
            $this->lpa->document->type = 'property-and-financial-affairs';
        }

        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LPA120');

        if($this->lpa->document->type == Document::LPA_TYPE_PF) {
            $this->assertEquals('property-and-financial-affairs', $formData['lpa-type']);
        }
        else {
            $this->assertEquals('health-and-welfare', $formData['lpa-type']);
        }

    }

    public function testExemption()
    {
        //  Unit tests do not execute without pdftk installed to container - the code needs to be restructured to allow mocking
        $this->markTestSkipped();

        $this->lpa->payment->reducedFeeReceivesBenefits = true;
        $this->lpa->payment->reducedFeeAwardedDamages = true;
        $this->lpa->payment->reducedFeeUniversalCredit = null;
        $this->lpa->payment->reducedFeeLowIncome = null;
        $this->lpa->payment->amount = 0;
        $this->lpa->payment->method = null;

        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LPA120');

        $this->assertEquals('yes', $formData['receive-benefits']);
        $this->assertEquals('no', $formData['damage-awarded']);
        $this->assertEquals(null, $formData['low-income']);
        $this->assertEquals(null, $formData['receive-universal-credit']);
    }

    public function testUniversalCredit()
    {
        //  Unit tests do not execute without pdftk installed to container - the code needs to be restructured to allow mocking
        $this->markTestSkipped();

        $this->lpa->payment->reducedFeeReceivesBenefits = false;
        $this->lpa->payment->reducedFeeAwardedDamages = null;
        $this->lpa->payment->reducedFeeUniversalCredit = true;
        $this->lpa->payment->reducedFeeLowIncome = null;
        $this->lpa->payment->amount = null;
        $this->lpa->payment->method = null;

        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LPA120');

        $this->assertEquals('no', $formData['receive-benefits']);
        $this->assertEquals(null, $formData['damage-awarded']);
        $this->assertEquals(null, $formData['low-income']);
        $this->assertEquals('yes', $formData['receive-universal-credit']);
    }

    public function testLowIncome()
    {
        //  Unit tests do not execute without pdftk installed to container - the code needs to be restructured to allow mocking
        $this->markTestSkipped();

        $this->lpa->payment->reducedFeeReceivesBenefits = false;
        $this->lpa->payment->reducedFeeAwardedDamages = null;
        $this->lpa->payment->reducedFeeUniversalCredit = null;
        $this->lpa->payment->reducedFeeLowIncome = true;
        $this->lpa->payment->amount = 55.00;
        $this->lpa->payment->method = 'cheque';

        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LPA120');

        $this->assertEquals('no', $formData['receive-benefits']);
        $this->assertEquals(null, $formData['damage-awarded']);
        $this->assertEquals('yes', $formData['low-income']);
        $this->assertEquals(null, $formData['receive-universal-credit']);
    }
}