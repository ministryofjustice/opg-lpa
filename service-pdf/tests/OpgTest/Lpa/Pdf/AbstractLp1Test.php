<?php
namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Lp1f;
use OpgTest\Lpa\Pdf\AbstractPdfTestClass;
use PHPUnit\Framework\TestCase;


/**
 * Tests of the AbstractLp1 test through its concrete subclasses.
 */
class AbstractLp1Test extends AbstractPdfTestClass
{
    public function testPopulatePageTwoThreeFour_SinglePrimaryAttorney()
    {
        $data = $this->getPfLpaJSON();

        // Modify LPA data so it has a single primary attorney
        $primaryAttorneys = $data["document"]["primaryAttorneys"];
        $data["document"]["primaryAttorneys"] = array_slice($primaryAttorneys, 0, 1);

        // We also modify who is registering, so it references the single
        // attorney rather than all of them
        $data["document"]["whoIsRegistering"] = ["1"];

        // Load the data to make our amended LPA
        $lpa = $this->buildLpaFromJSON($data);
        $pdf = new Lp1f($lpa, [], $this->factory);
        $pdf->generate();

        /* DATA */
        // Check the single attorney's data will be injected into the PDF
        $expectedData = [
            'name-title' => 'Mrs',
            'name-first' => 'Amy',
            'name-last' => 'Wheeler',
            'dob-date-day' => '10',
            'dob-date-month' => '05',
            'dob-date-year' => '1975',
            'address-address1' => 'Brickhill Cottage',
            'address-address2' => 'Birch Cross',
            'address-address3' => 'Marchington, Uttoxeter, Staffordshire',
            'address-postcode' => 'ST14 8NX',
            'email-address' => "\nopglpademo+AmyWheeler@gmail.com"
        ];

        // Get data which will be injected into the output PDF
        $actualData = $this->getReflectionPropertyValue('data', $pdf);

        // Stored here to prevent repetition; in the data, each of the
        // $expectedData keys will be prefixed with this string
        $prefix = 'lpa-document-primaryAttorneys-0-';

        foreach ($expectedData as $expectedKey => $expectedValue) {
            $expectedKey = "${prefix}${expectedKey}";
            $this->assertEquals($actualData[$expectedKey], $expectedValue);
        }

        /* STRIKETHROUGHS */
        // Check that there is a strikethrough on the second page for
        // attorneys (as we only have a single attorney)
        $actualStrikeThroughs = $this->getReflectionPropertyValue('strikeThroughTargets', $pdf);

        $expectedStrikeThroughs = [
            // single strikethrough on first attorney page
            1 => ['primaryAttorney-1-pf'],

            // two strikethroughs on second attorney page
            2 => ['primaryAttorney-2', 'primaryAttorney-3']
        ];

        foreach ($expectedStrikeThroughs as $expectedPage => $expectedAreas) {
            $this->assertEquals($actualStrikeThroughs[$expectedPage], $expectedAreas);
        }
    }
}
