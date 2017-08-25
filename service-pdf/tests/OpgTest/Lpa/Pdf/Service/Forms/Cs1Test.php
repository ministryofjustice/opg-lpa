<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Cs1;
use mikehaertl\pdftk\Pdf;

class Cs1Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $cs1 = new Cs1($lpa);

        $interFileStack = $cs1->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS1', $interFileStack);
        $this->assertCount(2, $interFileStack['CS1']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS1'], 'CS1');

        $pdf = $cs1->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            [
                'cs1-donor-full-name' => "Mrs Nancy Garrison",
                'cs1-footer-right' => "LPC Continuation sheet 1 (07.15)",
                'cs1-0-is' => "primaryAttorney",
                'cs1-0-name-title' => "Dr",
                'cs1-0-name-first' => "Henry",
                'cs1-0-name-last' => "Taylor",
                'cs1-0-address-address1' => "Lark Meadow Drive",
                'cs1-0-address-address2' => "Solihull",
                'cs1-0-address-address3' => "Birmingham",
                'cs1-0-address-postcode' => "B37 6NA",
                'cs1-0-dob-date-day' => "10",
                'cs1-0-dob-date-month' => "09",
                'cs1-0-dob-date-year' => "1973",
                'cs1-0-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
                'cs1-1-is' => "primaryAttorney",
                'cs1-1-name-title' => "Mr",
                'cs1-1-name-first' => "Elliot",
                'cs1-1-name-last' => "Sanders",
                'cs1-1-address-address1' => "12 Church Lane",
                'cs1-1-address-address2' => "Brierfield",
                'cs1-1-address-address3' => "Lancashire",
                'cs1-1-address-postcode' => "L21 4WL",
                'cs1-1-dob-date-day' => "10",
                'cs1-1-dob-date-month' => "10",
                'cs1-1-dob-date-year' => "1987",
                'cs1-1-email-address' => "\nopglpademo+ElliotSanders@gmail.com",
            ],
            [
                'cs1-donor-full-name' => "Mrs Nancy Garrison",
                'cs1-footer-right' => "LPC Continuation sheet 1 (07.15)",
                'cs1-0-is' => "replacementAttorney",
                'cs1-0-name-title' => "Ms",
                'cs1-0-name-first' => "Erica",
                'cs1-0-name-last' => "Schmidt",
                'cs1-0-address-address1' => "3 Westway",
                'cs1-0-address-address2' => "Stapleton, Taunton",
                'cs1-0-address-address3' => "",
                'cs1-0-address-postcode' => "TA2 9HP",
                'cs1-0-dob-date-day' => "11",
                'cs1-0-dob-date-month' => "04",
                'cs1-0-dob-date-year' => "1972",
                'cs1-1-is' => "peopleToNotify",
                'cs1-1-name-title' => "Mrs",
                'cs1-1-name-first' => "Liyana",
                'cs1-1-name-last' => "Gonzalez",
                'cs1-1-address-address1' => "33 New Street",
                'cs1-1-address-address2' => "Mossley",
                'cs1-1-address-address3' => "Greater Manchester",
                'cs1-1-address-postcode' => "MK47 9WD",
            ],
        ];

        //  Loop through the CS1 documents and assert the data
        foreach ($cs1->getCs1s() as $i => $cs1Pdf) {
            $this->assertEquals($expectedData[$i], $this->extractPdfFormData($cs1Pdf));
        }

        //  Confirm the crossed lines data is as expected
        $expectedCrossedLines = [];

        $this->assertEquals($expectedCrossedLines, $this->extractCrossedLines($cs1));
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $cs1 = new Cs1($lpa);

        $interFileStack = $cs1->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS1', $interFileStack);
        $this->assertCount(2, $interFileStack['CS1']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS1'], 'CS1');

        $pdf = $cs1->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            [
                'cs1-donor-full-name' => "Mrs Nancy Garrison",
                'cs1-footer-right' => "LPC Continuation sheet 1 (07.15)",
                'cs1-0-is' => "primaryAttorney",
                'cs1-0-name-title' => "Mr",
                'cs1-0-name-first' => "Elliot",
                'cs1-0-name-last' => "Sanders",
                'cs1-0-address-address1' => "12 Church Lane",
                'cs1-0-address-address2' => "Brierfield",
                'cs1-0-address-address3' => "Lancashire",
                'cs1-0-address-postcode' => "L21 4WL",
                'cs1-0-dob-date-day' => "10",
                'cs1-0-dob-date-month' => "10",
                'cs1-0-dob-date-year' => "1987",
                'cs1-0-email-address' => "\nopglpademo+ElliotSanders@gmail.com",
                'cs1-1-is' => "replacementAttorney",
                'cs1-1-name-title' => "Ms",
                'cs1-1-name-first' => "Erica",
                'cs1-1-name-last' => "Schmidt",
                'cs1-1-address-address1' => "3 Westway",
                'cs1-1-address-address2' => "Stapleton, Taunton",
                'cs1-1-address-address3' => "",
                'cs1-1-address-postcode' => "TA2 9HP",
                'cs1-1-dob-date-day' => "11",
                'cs1-1-dob-date-month' => "04",
                'cs1-1-dob-date-year' => "1972",
            ],
            [
                'cs1-donor-full-name' => "Mrs Nancy Garrison",
                'cs1-footer-right' => "LPC Continuation sheet 1 (07.15)",
                'cs1-0-is' => "peopleToNotify",
                'cs1-0-name-title' => "Mrs",
                'cs1-0-name-first' => "Liyana",
                'cs1-0-name-last' => "Gonzalez",
                'cs1-0-address-address1' => "33 New Street",
                'cs1-0-address-address2' => "Mossley",
                'cs1-0-address-address3' => "Greater Manchester",
                'cs1-0-address-postcode' => "MK47 9WD",
            ],
        ];

        //  Loop through the CS1 documents and assert the data
        foreach ($cs1->getCs1s() as $i => $cs1Pdf) {
            $this->assertEquals($expectedData[$i], $this->extractPdfFormData($cs1Pdf));
        }

        //  Confirm the crossed lines data is as expected
        $expectedCrossedLines = [
            0 => [
                'cs1'
            ],
        ];

        $this->assertEquals($expectedCrossedLines, $this->extractCrossedLines($cs1));
    }
}
