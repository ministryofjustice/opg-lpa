<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp3;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use mikehaertl\pdftk\Pdf;

class Lp3Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'lpa-document-peopleToNotify-name-title' => "Mrs",
            'lpa-document-peopleToNotify-name-first' => "Liyana",
            'lpa-document-peopleToNotify-name-last' => "Gonzalez",
            'lpa-document-peopleToNotify-address-address1' => "33 New Street",
            'lpa-document-peopleToNotify-address-address2' => "Mossley",
            'lpa-document-peopleToNotify-address-address3' => "Greater Manchester",
            'lpa-document-peopleToNotify-address-postcode' => "MK47 9WD",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "property-and-financial-affairs",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGeneratePFNoPeopleToNotifyException()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Remove the people to notify
        $lpa->document->peopleToNotify = [];

        $lp3 = new Lp3($lpa);

        $this->setExpectedException('RuntimeException', 'LP3 is not available for this LPA.');

        $lp3->generate();
    }

    public function testGeneratePFSinglePrimaryAttorney()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'lpa-document-peopleToNotify-name-title' => "Mrs",
            'lpa-document-peopleToNotify-name-first' => "Liyana",
            'lpa-document-peopleToNotify-name-last' => "Gonzalez",
            'lpa-document-peopleToNotify-address-address1' => "33 New Street",
            'lpa-document-peopleToNotify-address-address2' => "Mossley",
            'lpa-document-peopleToNotify-address-address3' => "Greater Manchester",
            'lpa-document-peopleToNotify-address-postcode' => "MK47 9WD",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "property-and-financial-affairs",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGeneratePFDonorRegistering()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the person registering the LPA to the donor
        $lpa->document->whoIsRegistering = 'donor';

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'lpa-document-peopleToNotify-name-title' => "Mrs",
            'lpa-document-peopleToNotify-name-first' => "Liyana",
            'lpa-document-peopleToNotify-name-last' => "Gonzalez",
            'lpa-document-peopleToNotify-address-address1' => "33 New Street",
            'lpa-document-peopleToNotify-address-address2' => "Mossley",
            'lpa-document-peopleToNotify-address-address3' => "Greater Manchester",
            'lpa-document-peopleToNotify-address-postcode' => "MK47 9WD",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "donor",
            'lpa-type' => "property-and-financial-affairs",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGeneratePFTrustAttorneyOnly()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Ensure that there is only a trust primary attorney
        foreach ($lpa->document->primaryAttorneys as $attorneyKey => $attorney) {
            if ($attorney instanceof TrustCorporation) {
                //  Set the trust as the only attorney registering the LPA
                $lpa->document->whoIsRegistering = [$attorney->id];
            } else {
                //  Remove the attorney
                unset($lpa->document->primaryAttorneys[$attorneyKey]);
            }
        }

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'lpa-document-peopleToNotify-name-title' => "Mrs",
            'lpa-document-peopleToNotify-name-first' => "Liyana",
            'lpa-document-peopleToNotify-name-last' => "Gonzalez",
            'lpa-document-peopleToNotify-address-address1' => "33 New Street",
            'lpa-document-peopleToNotify-address-address2' => "Mossley",
            'lpa-document-peopleToNotify-address-address3' => "Greater Manchester",
            'lpa-document-peopleToNotify-address-postcode' => "MK47 9WD",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "property-and-financial-affairs",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'lpa-document-primaryAttorneys-0-name-last' => "Standard Trust",
            'lpa-document-primaryAttorneys-0-address-address1' => "1 Laburnum Place",
            'lpa-document-primaryAttorneys-0-address-address2' => "Sketty",
            'lpa-document-primaryAttorneys-0-address-address3' => "Swansea, Abertawe",
            'lpa-document-primaryAttorneys-0-address-postcode' => "SA2 8HT",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'lpa-document-peopleToNotify-name-title' => "Mrs",
            'lpa-document-peopleToNotify-name-first' => "Liyana",
            'lpa-document-peopleToNotify-name-last' => "Gonzalez",
            'lpa-document-peopleToNotify-address-address1' => "33 New Street",
            'lpa-document-peopleToNotify-address-address2' => "Mossley",
            'lpa-document-peopleToNotify-address-address3' => "Greater Manchester",
            'lpa-document-peopleToNotify-address-postcode' => "MK47 9WD",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "health-and-welfare",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateHWNoPeopleToNotifyException()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Remove the people to notify
        $lpa->document->peopleToNotify = [];

        $lp3 = new Lp3($lpa);

        $this->setExpectedException('RuntimeException', 'LP3 is not available for this LPA.');

        $lp3->generate();
    }

    public function testGenerateHWSinglePrimaryAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'lpa-document-peopleToNotify-name-title' => "Mrs",
            'lpa-document-peopleToNotify-name-first' => "Liyana",
            'lpa-document-peopleToNotify-name-last' => "Gonzalez",
            'lpa-document-peopleToNotify-address-address1' => "33 New Street",
            'lpa-document-peopleToNotify-address-address2' => "Mossley",
            'lpa-document-peopleToNotify-address-address3' => "Greater Manchester",
            'lpa-document-peopleToNotify-address-postcode' => "MK47 9WD",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "health-and-welfare",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateHWDonorRegistering()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change the person registering the LPA to the donor
        $lpa->document->whoIsRegistering = 'donor';

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        $pdf = $form->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'lpa-document-peopleToNotify-name-title' => "Mrs",
            'lpa-document-peopleToNotify-name-first' => "Liyana",
            'lpa-document-peopleToNotify-name-last' => "Gonzalez",
            'lpa-document-peopleToNotify-address-address1' => "33 New Street",
            'lpa-document-peopleToNotify-address-address2' => "Mossley",
            'lpa-document-peopleToNotify-address-address3' => "Greater Manchester",
            'lpa-document-peopleToNotify-address-postcode' => "MK47 9WD",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "donor",
            'lpa-type' => "health-and-welfare",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
