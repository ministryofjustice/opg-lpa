<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\Pdf\Lpa120;
use Exception;

class Lpa120Test extends AbstractPdfTestClass
{
    public function testConstructorThrowsExceptionNotEnoughData()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate Opg\Lpa\Pdf\Lpa120');

        new Lpa120(new Lpa());
    }

    public function testGeneratePF()
    {
        $lpa = $this->getLpa();

        $pdf = new Lpa120($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPA120.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [];

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

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lpa120.pdf');
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

    public function testGeneratePFAttorneyCorrespondentEnteredManually()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an attorney
        $lpa->document->correspondent->who = 'attorney';

        $pdf = new Lpa120($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPA120.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [];

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

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lpa120.pdf');
    }

    public function testGeneratePFOtherCorrespondentEnteredManually()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an other party
        $lpa->document->correspondent->who = 'other';

        $pdf = new Lpa120($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPA120.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [];

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

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lpa120.pdf');
    }

    public function testGeneratePFDonorCorrespondent()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to the donor and remove the manually entered data
        $lpa->document->whoIsRegistering = 'donor';

        $pdf = new Lpa120($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPA120.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [];

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

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lpa120.pdf');
    }

    public function testGeneratePFApplicantTitleOther()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent title to a custom value
        $lpa->document->correspondent->name->title = 'Sir';

        $pdf = new Lpa120($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPA120.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [];

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

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lpa120.pdf');
    }

    public function testGeneratePFBooleanAsNo()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change a value to return a "No" for a false boolean
        $lpa->payment->reducedFeeReceivesBenefits = false;

        $pdf = new Lpa120($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPA120.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [];

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

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lpa120.pdf');
    }
}
