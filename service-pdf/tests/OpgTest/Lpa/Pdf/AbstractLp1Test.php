<?php
namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Lp1f;
use OpgTest\Lpa\Pdf\AbstractPdfTestClass;
use PHPUnit\Framework\TestCase;


/**
 * Tests of the AbstractLp1 test through its concrete subclasses.
 * The intention of this set of tests is to find a fast way to test the
 * modifications to the data and strikethroughs injected into the PDF,
 * while only requiring generation of a single PDF to cover all the test cases.
 *
 * To this end we have a single test method, but pairs of *Data and *Assertions
 * private methods to apply necessary modifications to the data and make
 * assertions about the data and strikethroughs which will be added to the
 * generated PDF.
 *
 * This only works so long as we don't need to test different input data
 * for a single page: for example, it's not possible to test both a single
 * primary attorney and multiple primary attorneys by generating a single PDF.
 * However, these tests are a supplement to the other tests specifically for
 * concrete classes which test alternative input data, and are designed to fill
 * holes in test coverage left by those other, more ponderous tests.
 *
 * In cases where we need to test different input data, we will have to
 * generate additional PDFs and test those separately. But while the data
 * we're testing is disjoint, we can create a single PDF and apply multiple
 * test cases to it.
 */
class AbstractLp1Test extends AbstractPdfTestClass
{
    // data for testing populatePageTwoThreeFour()
    private function populatePageTwoThreeFour_singlePrimaryAttorneyData($data)
    {
        // Modify LPA data so it has a single primary attorney
        $primaryAttorneys = $data["document"]["primaryAttorneys"];
        $data["document"]["primaryAttorneys"] = array_slice($primaryAttorneys, 0, 1);

        // We also modify who is registering, so it references the single
        // attorney rather than all of them
        $data["document"]["whoIsRegistering"] = ["1"];

        return $data;
    }

    // assertions about data and strikethroughs added by populatePageTwoThreeFour()
    private function populatePageTwoThreeFour_singlePrimaryAttorneyAssertions($actualData, $actualStrikeThroughs)
    {
        /* DATA */
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

        // Stored here to prevent repetition; in the data, each of the
        // $expectedData keys will be prefixed with this string
        $prefix = 'lpa-document-primaryAttorneys-0-';

        foreach ($expectedData as $expectedKey => $expectedValue) {
            $expectedKey = "${prefix}${expectedKey}";
            $this->assertEquals($actualData[$expectedKey], $expectedValue);
        }

        /* STRIKETHROUGHS */
        $expectedStrikeThroughs = [
            // single strikethrough on first attorney page
            1 => ['primaryAttorney-1-pf'],

            // two strikethroughs on second attorney page
            2 => ['primaryAttorney-2', 'primaryAttorney-3']
        ];

        $this->assertArrayIsSubArrayOf($expectedStrikeThroughs, $actualStrikeThroughs);
    }

    // data for testing populatePageFive()
    private function populatePageFive_SingleTrustCorporationReplacementAttorneyData($data)
    {
        // Modify LPA data so it has a trust corporation as its single
        // replacement attorney
        $data["document"]["replacementAttorneys"] = json_decode('[
            {
                "name": "Standard Trust",
                "number": "678437685",
                "id": 1,
                "address": {
                    "address1": "1 Laburnum Place",
                    "address2": "Sketty",
                    "address3": "Swansea, Abertawe",
                    "postcode": "SA2 8HT"
                },
                "email": {
                    "address": "opglpademo+trustcorp@gmail.com"
                },
                "type": "trust"
            }
        ]', TRUE);

        return $data;
    }

    // assertions about data and strikethroughs added by populatePageFive()
    private function populatePageFive_SingleTrustCorporationReplacementAttorneyAssertions($actualData, $actualStrikeThroughs)
    {
        /* DATA */
        $expectedData = [
            'replacement-attorney-0-is-trust-corporation' => 'On',
            'lpa-document-replacementAttorneys-0-name-last' => 'Standard Trust',
            'lpa-document-replacementAttorneys-0-address-address1' => '1 Laburnum Place',
            'lpa-document-replacementAttorneys-0-address-address2' => 'Sketty',
            'lpa-document-replacementAttorneys-0-address-address3' => 'Swansea, Abertawe',
            'lpa-document-replacementAttorneys-0-address-postcode' => 'SA2 8HT',
            'lpa-document-replacementAttorneys-0-email-address' => "\nopglpademo+trustcorp@gmail.com",
        ];

        $this->assertArrayIsSubArrayOf($expectedData, $actualData);

        /* STRIKETHROUGHS */
        $expectedStrikeThroughs = [
            // one strikethrough on replacement attorney page 4;
            // NB if there are more than 2 replacement attorneys, they are
            // added on continuation sheet 1, so we won't see strikethroughs
            // for them in this test as we only have one replacement attorney
            // and no continuation sheet
            4 => ['replacementAttorney-1-pf'],
        ];

        $this->assertArrayIsSubArrayOf($expectedStrikeThroughs, $actualStrikeThroughs);
    }

    // data for testing populatePageSeven()
    private function populatePageSeven_SinglePersonToNotifyData($data)
    {
        // TODO Set a single person to notify so we get three strikethroughs
        // on page 7
        return $data;
    }

    // assertions about data and strikethroughs added by populatePageSeven()
    private function populatePageSeven_SinglePersonToNotifyAssertions($data)
    {
        // TODO
        $this->assertTrue(TRUE);
    }

    // main test function
    public function testPopulatePages()
    {
        $data = $this->getPfLpaJSON();

        // Modify the LPA data to produce what we need for our test cases
        $data = $this->populatePageTwoThreeFour_SinglePrimaryAttorneyData($data);
        $data = $this->populatePageFive_SingleTrustCorporationReplacementAttorneyData($data);
        $data = $this->populatePageSeven_SinglePersonToNotifyData($data);

        // Load the data to make our amended LPA
        $lpa = $this->buildLpaFromJSON($data);
        $pdf = new Lp1f($lpa, [], $this->factory);
        $pdf->generate();

        // Get data which will be injected into the output PDF
        $actualData = $this->getReflectionPropertyValue('data', $pdf);

        // Get strikethroughs which will be applied on the output PDF
        $actualStrikeThroughs = $this->getReflectionPropertyValue('strikeThroughTargets', $pdf);

        // Perform assertions
        $this->populatePageTwoThreeFour_SinglePrimaryAttorneyAssertions($actualData, $actualStrikeThroughs);
        $this->populatePageFive_SingleTrustCorporationReplacementAttorneyAssertions($actualData, $actualStrikeThroughs);
        $this->populatePageSeven_SinglePersonToNotifyAssertions($actualData, $actualStrikeThroughs);
    }
}
