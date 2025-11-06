<?php

namespace OpgTest\Lpa\Pdf;

use DateTimeImmutable;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\Pdf\Lpa120;
use Exception;

class Lpa120Test extends AbstractPdfTestCase
{
    private function verifyPdf($lpa, $data, $pageShift)
    {
        $constituentPdfs = [];
        $feeEffectiveDate = new DateTimeImmutable(getenv('LPA_FEE_EFFECTIVE_DATE') ?: '2025-11-17T00:00:00');
        $timeNow = new DateTimeImmutable('now');
        $templateFileName = ($timeNow >= $feeEffectiveDate) ? 'LPA120_2025_fee.pdf' : 'LPA120.pdf';

        $pdf = new Lpa120($lpa);

        $this->verifyExpectedPdfData($pdf, $templateFileName, $this->strikeThroughTargets, $this->blankTargets, $constituentPdfs, $data, $pageShift, $this->formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lpa120.pdf');

        $this->visualDiffCheck($pdf, 'tests/visualdiffpdfs/1762449985.0974-A510-7295-5715-Lpa120.pdf');
    }


    public function testConstructorThrowsExceptionNotEnoughData()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate Opg\Lpa\Pdf\Lpa120');

        new Lpa120(new Lpa());
    }

    public function testGenerateNoRepeatCaseNumberException()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Remove the repeat case number and blank the payment
        $lpa->repeatCaseNumber = null;
        $lpa->payment = new Payment();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate Opg\Lpa\Pdf\Lpa120');

        $pdf = new Lpa120($lpa);
    }

    public function testGeneratePF()
    {
        $lpa = $this->getLpa();

        $data = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => 12345678,
            'applicant-type' => "donor",
            'applicant-type-other' => "",
            'applicant-name-title' => "mrs",
            'applicant-name-title-other' => "",
            'applicant-name-first' => "Nancy",
            'applicant-name-last' => "Garrison",
            'applicant-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'applicant-phone-number' => "01234 123456",
            'applicant-email-address' => "opglpademo+LouiseJames@gmail.com",
            'receive-benefits' => "",
            'damage-awarded' => "",
            'low-income' => "",
            'receive-universal-credit' => "yes",
        ];

        $pageShift = 0;

        $this->verifyPdf($lpa, $data, $pageShift);
    }

    public function testGeneratePFAttorneyCorrespondentEnteredManually()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an attorney
        $lpa->document->correspondent->who = 'attorney';

        $data = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => 12345678,
            'applicant-type' => "attorney",
            'applicant-type-other' => "",
            'applicant-name-title' => "mrs",
            'applicant-name-title-other' => "",
            'applicant-name-first' => "Nancy",
            'applicant-name-last' => "Garrison",
            'applicant-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'applicant-phone-number' => "01234 123456",
            'applicant-email-address' => "opglpademo+LouiseJames@gmail.com",
            'receive-benefits' => "",
            'damage-awarded' => "",
            'low-income' => "",
            'receive-universal-credit' => "yes",
        ];

        $pageShift = 0;

        $this->verifyPdf($lpa, $data, $pageShift);
    }

    public function testGeneratePFOtherCorrespondentEnteredManually()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an other party
        $lpa->document->correspondent->who = 'other';

        $data = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => 12345678,
            'applicant-type' => "other",
            'applicant-type-other' => "Correspondent",
            'applicant-name-title' => "mrs",
            'applicant-name-title-other' => "",
            'applicant-name-first' => "Nancy",
            'applicant-name-last' => "Garrison",
            'applicant-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'applicant-phone-number' => "01234 123456",
            'applicant-email-address' => "opglpademo+LouiseJames@gmail.com",
            'receive-benefits' => "",
            'damage-awarded' => "",
            'low-income' => "",
            'receive-universal-credit' => "yes",
        ];

        $pageShift = 0;

        $this->verifyPdf($lpa, $data, $pageShift);
    }

    public function testGeneratePFDonorCorrespondent()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to the donor and remove the manually entered data
        $lpa->document->whoIsRegistering = 'donor';

        $data = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => 12345678,
            'applicant-type' => "donor",
            'applicant-type-other' => "",
            'applicant-name-title' => "mrs",
            'applicant-name-title-other' => "",
            'applicant-name-first' => "Nancy",
            'applicant-name-last' => "Garrison",
            'applicant-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'applicant-phone-number' => "01234 123456",
            'applicant-email-address' => "opglpademo+LouiseJames@gmail.com",
            'receive-benefits' => "",
            'damage-awarded' => "",
            'low-income' => "",
            'receive-universal-credit' => "yes",
        ];

        $pageShift = 0;

        $this->verifyPdf($lpa, $data, $pageShift);
    }

    public function testGeneratePFApplicantTitleOther()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent title to a custom value
        $lpa->document->correspondent->name->title = 'Sir';

        $data = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => 12345678,
            'applicant-type' => "donor",
            'applicant-type-other' => "",
            'applicant-name-title' => "other",
            'applicant-name-title-other' => "Sir",
            'applicant-name-first' => "Nancy",
            'applicant-name-last' => "Garrison",
            'applicant-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'applicant-phone-number' => "01234 123456",
            'applicant-email-address' => "opglpademo+LouiseJames@gmail.com",
            'receive-benefits' => "",
            'damage-awarded' => "",
            'low-income' => "",
            'receive-universal-credit' => "yes",
        ];

        $pageShift = 0;

        $this->verifyPdf($lpa, $data, $pageShift);
    }

    public function testGeneratePFBooleanAsNo()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change a value to return a "No" for a false boolean
        $lpa->payment->reducedFeeReceivesBenefits = false;

        $data = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => 12345678,
            'applicant-type' => "donor",
            'applicant-type-other' => "",
            'applicant-name-title' => "mrs",
            'applicant-name-title-other' => "",
            'applicant-name-first' => "Nancy",
            'applicant-name-last' => "Garrison",
            'applicant-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'applicant-phone-number' => "01234 123456",
            'applicant-email-address' => "opglpademo+LouiseJames@gmail.com",
            'receive-benefits' => "no",
            'damage-awarded' => "",
            'low-income' => "",
            'receive-universal-credit' => "yes",
        ];

        $pageShift = 0;

        $this->verifyPdf($lpa, $data, $pageShift);
    }
}
