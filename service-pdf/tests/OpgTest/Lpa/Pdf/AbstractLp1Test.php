<?php

namespace OpgTest\Lpa\Pdf;

use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\Pdf\Lp1f;
use Opg\Lpa\Pdf\Lp1h;
use Opg\Lpa\Pdf\Traits\LongContentTrait;

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
class AbstractLp1Test extends AbstractPdfTestCase
{
    use LongContentTrait;

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
            $expectedKey = sprintf('%s%s',$prefix, $expectedKey);
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
        ]', true);

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
        // Set a single person to notify so we get three strikethroughs
        // on page 7
        $peopleToNotify = $data['document']['peopleToNotify'];
        $data['document']['peopleToNotify'] = array_slice($peopleToNotify, 0, 1);

        return $data;
    }

    // assertions about data and strikethroughs added by populatePageSeven()
    private function populatePageSeven_SinglePersonToNotifyAssertions($actualData, $actualStrikeThroughs)
    {
        /* DATA */
        $expectedData = [
            'lpa-document-peopleToNotify-0-name-title' => 'Mr',
            'lpa-document-peopleToNotify-0-name-first' => 'Anthony',
            'lpa-document-peopleToNotify-0-name-last' => 'Webb',
            'lpa-document-peopleToNotify-0-address-address1' => 'Brickhill Cottage',
            'lpa-document-peopleToNotify-0-address-address2' => 'Birch Cross',
            'lpa-document-peopleToNotify-0-address-address3' => 'Marchington, Uttoxeter, Staffordshire',
            'lpa-document-peopleToNotify-0-address-postcode' => 'BS18 6PL',
        ];

        $this->assertArrayIsSubArrayOf($expectedData, $actualData);

        /* STRIKETHROUGHS */
        $expectedStrikeThroughs = [
            6 => ['people-to-notify-1', 'people-to-notify-2', 'people-to-notify-3'],
        ];

        $this->assertArrayIsSubArrayOf($expectedStrikeThroughs, $actualStrikeThroughs);
    }

    private function populatePageEight_NoPreferencesAndLongInstructionsData($data)
    {
        // Set empty preferences
        $data['document']['preference'] = '';

        // Deliberately make instructions overflow the instructions text box
        $instructionsMaxSize = $this->getInstructionsPreferencesBoxSize();
        $data['document']['instruction'] = str_repeat('hi ', intval($instructionsMaxSize / 3) + 3);

        return $data;
    }

    private function populatePageEight_NoPreferencesAndLongInstructionsAssertions($actualData, $actualStrikeThroughs)
    {
        /* DATA */
        // Should see check in "has more instructions" checkbox
        $expectedData = ['has-more-instructions' => 'On'];
        $this->assertArrayIsSubArrayOf($expectedData, $actualData);

        /* STRIKETHROUGHS */
        // Expect preferences field to have a strikethrough
        $expectedStrikeThroughs = [7 => ['preference']];
        $this->assertArrayIsSubArrayOf($expectedStrikeThroughs, $actualStrikeThroughs);
    }

    private function populatePageEighteen_CertificateProviderCorrespondentData($data)
    {
        // Set certificate provider as correspondent
        $data['document']['correspondent']['who'] = Correspondence::WHO_CERTIFICATE_PROVIDER;
        $data['document']['correspondent']['company'] = 'Yay Toys';

        return $data;
    }

    private function populatePageEighteen_CertificateProviderCorrespondentAssertions($actualData)
    {
        /* DATA */
        $expectedData = [
            'lpa-document-correspondent-name-title' => 'Mrs',
            'lpa-document-correspondent-name-first' => 'Nancy',
            'lpa-document-correspondent-name-last' => 'Garrison',
            'lpa-document-correspondent-company' => 'Yay Toys',
            'lpa-document-correspondent-address-address1' => 'Bank End Farm House',
            'lpa-document-correspondent-address-address2' => 'Undercliff Drive',
            'lpa-document-correspondent-address-address3' => 'Ventnor, Isle of Wight',
            'lpa-document-correspondent-address-postcode' => 'PO38 1UL',
        ];

        $this->assertArrayIsSubArrayOf($expectedData, $actualData);
    }

    // main test function - this encapsulates most of the tests while only
    // requiring the PDF to be generated once
    public function testPopulatePages()
    {
        $data = $this->getPfLpaJSON();

        // Modify the LPA data to produce what we need for our test cases
        $data = $this->populatePageTwoThreeFour_SinglePrimaryAttorneyData($data);
        $data = $this->populatePageFive_SingleTrustCorporationReplacementAttorneyData($data);
        $data = $this->populatePageSeven_SinglePersonToNotifyData($data);
        $data = $this->populatePageEight_NoPreferencesAndLongInstructionsData($data);
        $data = $this->populatePageEighteen_CertificateProviderCorrespondentData($data);

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
        $this->populatePageEight_NoPreferencesAndLongInstructionsAssertions($actualData, $actualStrikeThroughs);
        $this->populatePageEighteen_CertificateProviderCorrespondentAssertions($actualData);
    }

    // Additional tests which can't be performed in testPopulatePages(), as they
    // contradict the data set by that test (e.g. we set empty preferences in the
    // main testPopulatePages() but also need to set really long preferences to
    // force the continuation sheet, which we do in this test instead)
    public function testPopulatePagesAlternatives()
    {
        // Make this one a Health and Welfare LPA so that we can test strikethroughs on
        // page six
        $data = $this->getHwLpaJSON();

        // Amend primary attorney decisions to "depends", which adds a
        // continuation sheet with an explanation of how the attorney decisions
        // have to be taken; also need to set the explanation text
        $data['document']['primaryAttorneyDecisions']['how'] =
            PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS;

        $data['document']['primaryAttorneyDecisions']['howDetails'] =
            'Long explanation of how primary attorneys should make decisions';

        // Amend when primary attorney decisions can be made; this ensures that
        // page six has a strike through for the "when" decision which was not chosen
        // (i.e. "when no capacity")
        $data['document']['primaryAttorneyDecisions']['when'] =
            PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW;

        // Remove any trust corporation attorneys; this ensures we *don't* get
        // continuation sheet 4 (see addContinuationSheets())
        foreach ($data['document']['primaryAttorneys'] as $i => $primaryAttorney) {
            if ($primaryAttorney['type'] === 'trust') {
                // remove the corresponding key from whoIsRegistering
                $id = '' . $primaryAttorney['id'];
                $whoIsRegisteringKey = array_search($id, $data['document']['whoIsRegistering']);
                if ($whoIsRegisteringKey !== false) {
                    unset($data['document']['whoIsRegistering'][$whoIsRegisteringKey]);
                }

                // remove the primary attorney itself
                unset($data['document']['primaryAttorneys'][$i]);
            }
        }

        // Make a primary attorney the correspondent; this adds a strikethrough for
        // the correspondent address (see populatePageEighteen())
        $data['document']['correspondent']['who'] = Correspondence::WHO_ATTORNEY;

        // Amend the preferences to be really long - this uses the continuation
        // sheet for preferences; note that the testPopulatePages() test also
        // has this continuation sheet, but the extended notes are in
        // the instructions box rather than preferences box
        $preferencesMaxSize = $this->getInstructionsPreferencesBoxSize();
        $data['document']['preference'] = str_repeat(
            'hello ',
            intval($preferencesMaxSize / 6) + 6
        );

        // Remove the payment. This prevents the LPA from being treated as complete,
        // which in turn means that generate() will create a stamped draft,
        // exercising the AbstractLp1->stampPageWith() method.
        unset($data['payment']);

        // Load the data to make our amended LPA
        $lpa = $this->buildLpaFromJSON($data);
        $pdf = new Lp1h($lpa, [], $this->factory);
        $pdf->generate();

        // Get data which will be injected into the output PDF
        $actualData = $this->getReflectionPropertyValue('data', $pdf);

        // Get strikethroughs which will be applied to the output PDF
        $actualStrikeThroughs = $this->getReflectionPropertyValue('strikeThroughTargets', $pdf);

        // Get continuation sheets which will be included with the PDF;
        // we do the double json_encode/json_decode to get the JSON into a
        // stripped down associative array format we can more easily work with
        $actualContinuationSheets = $this->getReflectionPropertyValue('constituentPdfs', $pdf);
        $actualSheets = json_decode(json_encode($actualContinuationSheets), true);

        // Assert that the "has more preferences" checkbox is ticked
        $expectedData = ['has-more-preferences' => 'On'];
        $this->assertArrayIsSubArrayOf($expectedData, $actualData);

        // Assert we have a strikethrough for the correspondent address on page 17
        // and the "when no capacity" signature on page 6
        $expectedStrikeThroughs = [
            '17' => ['correspondent-empty-address'],
            '5' => ['life-sustain-B'],
        ];
        $this->assertArrayIsSubArrayOf($expectedStrikeThroughs, $actualStrikeThroughs);

        // Assert that we have a continuation sheet for primary attorney
        // decision making
        $expectedSheet = [
            'pdf' => [
                'class' => 'Opg\\Lpa\\Pdf\\ContinuationSheet3'
            ],
            'start' => 1,
            'pages' => 2
        ];

        // The continuation sheet should be a member of the array associated
        // with the 15 index in the $actualSheets array
        $this->assertContains($expectedSheet, $actualSheets['15']);

        // Verify we don't get continuation sheet 4
        // as we removed the trust corporation from primary attorneys
        foreach ($actualSheets['15'] as $i => $actualSheet) {
            if (is_array($actualSheet['pdf']) && array_key_exists('class', $actualSheet['pdf'])) {
                $this->assertNotEquals(
                    'Opg\\Lpa\\Pdf\\ContinuationSheet4',
                    $actualSheet['pdf']['class']
                );
            }
        }
    }
}
