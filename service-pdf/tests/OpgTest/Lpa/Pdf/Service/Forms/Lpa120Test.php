<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Exception;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Service\Forms\Lpa120;
use mikehaertl\pdftk\Pdf;
use RuntimeException;

class Lps120Test extends AbstractFormTestClass
{
    public function testConstructorThrowsExceptionNotEnoughData()
    {
        $this->setExpectedException('RuntimeException', 'LPA does not contain all the required data to generate a LPA120');

        new Lpa120(new Lpa());
    }

    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyTmpFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => "12345678",
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

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateNoRepeatCaseNumberException()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Remove the repeat case number and blank the payment
        $lpa->repeatCaseNumber = null;
        $lpa->payment = new Payment();

        $lpa120 = new Lpa120($lpa);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LPA120 is not available for this LPA.');

        $lpa120->generate();
    }

    public function testGeneratePFAttorneyCorrespondentEnteredManually()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an attorney
        $lpa->document->correspondent->who = 'attorney';

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyTmpFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => "12345678",
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

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGeneratePFOtherCorrespondentEnteredManually()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an other party
        $lpa->document->correspondent->who = 'other';

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyTmpFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => "12345678",
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

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGeneratePFDonorCorrespondent()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to the donor and remove the manually entered data
        $lpa->document->correspondent = null;
        $lpa->document->whoIsRegistering = 'donor';

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyTmpFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => "12345678",
            'applicant-type' => "donor",
            'applicant-type-other' => "",
            'applicant-name-title' => "mrs",
            'applicant-name-title-other' => "",
            'applicant-name-first' => "Nancy",
            'applicant-name-last' => "Garrison",
            'applicant-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'applicant-phone-number' => "",
            'applicant-email-address' => "opglpademo+LouiseJames@gmail.com",
            'receive-benefits' => "",
            'damage-awarded' => "",
            'low-income' => "",
            'receive-universal-credit' => "yes",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGeneratePFAttorneyCorrespondent()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an attorney and remove the manually entered data
        $lpa->document->correspondent = null;
        $lpa->document->whoIsRegistering = [1];

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyTmpFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => "12345678",
            'applicant-type' => "attorney",
            'applicant-type-other' => "",
            'applicant-name-title' => "mrs",
            'applicant-name-title-other' => "",
            'applicant-name-first' => "Amy",
            'applicant-name-last' => "Wheeler",
            'applicant-address' => "\nBrickhill Cottage, Birch Cross, Marchington, Uttoxeter, Staffordshire, ST14 8NX",
            'applicant-phone-number' => "",
            'applicant-email-address' => "opglpademo+AmyWheeler@gmail.com",
            'receive-benefits' => "",
            'damage-awarded' => "",
            'low-income' => "",
            'receive-universal-credit' => "yes",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGeneratePFOtherCorrespondentThrowsException()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an attorney and remove the manually entered data
        $lpa->document->correspondent = null;
        $lpa->document->whoIsRegistering = false;

        $lpa120 = new Lpa120($lpa);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('When generating LPA120, applicant was found invalid');

        $form = $lpa120->generate();
    }

    public function testGeneratePFApplicantTitleOther()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent title to a custom value
        $lpa->document->correspondent->name->title = 'Sir';

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyTmpFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => "12345678",
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

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGeneratePFBooleanAsNo()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change a value to return a "No" for a false boolean
        $lpa->payment->reducedFeeReceivesBenefits = false;

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyTmpFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'donor-full-name' => "Mrs Nancy Garrison",
            'donor-address' => "\nBank End Farm House, Undercliff Drive, Ventnor, Isle of Wight, PO38 1UL",
            'lpa-type' => "property-and-financial-affairs",
            'is-repeat-application' => "On",
            'case-number' => "12345678",
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

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
