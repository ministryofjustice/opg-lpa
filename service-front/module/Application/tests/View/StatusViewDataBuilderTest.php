<?php

declare(strict_types=1);

namespace ApplicationTest\View;

use Application\View\StatusViewDataBuilder;
use DateTime;
use DOMDocument;
use DOMXpath;
use Laminas\View\Model\ViewModel;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Document\Document;

/**
 * The intention of this test is to check which status will be shown on the page, and
 * which expected receipt date (if any), for different test cases which
 * were originally in the cypress integration tests.
 *
 * While we have controller unit tests, they don't check the output HTML in any way
 * to make sure that the correct element is highlighted (in the "done steps" bar
 * at the top of the status detail page). They also don't check that the expected
 * receipt date is shown in the correct format in the HTML.
 */
final class StatusViewDataBuilderTest extends MockeryTestCase
{
    private DateTime $trackFromDate;
    private int $expectedWorkingDaysBeforeReceipt = 15;
    private Document $document;

    /** @var array */
    private const PROGRESS_BAR_STEPS = ['waiting', 'received', 'checking', 'processed'];

    private ViewModelRenderer $renderer;

    public function setUp(): void
    {
        $this->trackFromDate = (new DateTime('now'))->modify('-7 days');

        $this->document = new Document([
            'correspondent' => [
                'name' => [
                    'title' => 'Ms',
                    'first' => 'Mecnodo',
                    'last' => 'Expodo',
                ]
            ],
        ]);

        $this->renderer = new ViewModelRenderer();
        $this->renderer->addFilter('format_lpa_id');
        $this->renderer->loadTemplate('application/authenticated/lpa/status/index.twig');
        $this->builder = new StatusViewDataBuilder();
    }

    private array $testCases = [
        // LPA which has not been received yet displays as "Waiting"
        [
            'lpaId' => '33718377316',
            'lpaMetadata' => [],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'waiting',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'waiting',
                'stepsDone' => [],
                'textFragments' => [
                    "We're waiting to confirm receipt of this LPA",
                    "we'll write to Ms Mecnodo Expodo",
                    "we've heard back from them",
                    "If we need more information about the application to pay a reduced or no fee"
                ]
            ],
        ],

        // LPA which has not been received yet and cannot generate LPA120
        // displays as "Waiting" without the additional "more information" text
        [
            'lpaId' => '63452156316',
            'lpaMetadata' => [],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'waiting',
            ],
            'lpaCanGenerateLPA120' => false,
            'expected' => [
                'status' => 'waiting',
                'stepsDone' => [],
                'notTextFragments' => [
                    "If we need more information about the application to pay a reduced or no fee"
                ]
            ],
        ],

        // deleted from Sirius shows as "Waiting"
        [
            'lpaId' => '97998888883',
            'lpaMetadata' => [
                'application-receipt-date' => '2021-10-03',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => true,
                'returnUnpaid' => null,
                'status' => 'waiting',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'waiting',
                'stepsDone' => [],
                'textFragments' => [
                    "We're waiting to confirm receipt of this LPA",
                    "we'll write to Ms Mecnodo Expodo",
                    "we've heard back from them",
                    "If we need more information about the application to pay a reduced or no fee"
                ]
            ],
        ],

        // LPA with received status displays as "Received"
        [
            'lpaId' => '91155453023',
            'lpaMetadata' => [
                'application-receipt-date' => '2021-02-28',
                'application-status-date' => '2021-02-28',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'received',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'received',
                'stepsDone' => ['waiting'],
                'textFragments' => [
                    "received the LPA",
                ]
            ],
        ],

        // LPA with receipt date only and checking status displays as "Checking"
        [
            'lpaId' => '78582508789',
            'lpaMetadata' => [
                'application-receipt-date' => '2022-03-01',
                'application-status-date' => '2022-03-04',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'checking',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'checking',
                'stepsDone' => ['waiting', 'received'],
                'textFragments' => [
                    "If there is something that must be corrected before the LPA can be registered",
                    "contact Ms Mecnodo Expodo",
                ]
            ],
        ],

        // registered LPA with no dispatch date displays as "Checking"
        [
            'lpaId' => '68582508781',
            'lpaMetadata' => [
                'application-receipt-date' => '2021-03-01',
                'application-registration-date' => '2021-03-04',
                'application-status-date' => '2021-03-04',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'checking',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'checking',
                'stepsDone' => ['waiting', 'received'],
                'textFragments' => [
                    "If there is something that must be corrected before the LPA can be registered",
                    "contact Ms Mecnodo Expodo",
                ]
            ],
        ],

        // registered and dispatched LPA displays as "Processed"
        // with dispatch date + 15 working days as expected receipt date
        // and text with a link to Use an LPA
        [
            'lpaId' => '32004638272',
            'lpaMetadata' => [
                'application-receipt-date' => '2021-05-01',
                'application-registration-date' => '2021-05-02',
                'application-status-date' => '2021-05-03',
                'application-dispatch-date' => '2021-05-03',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'processed',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'processed',
                'stepsDone' => ['waiting', 'received', 'checking'],
                'receiptDate' => '24/05/21',
                'textFragments' => [
                    "processed the LPA",

                    // should get this text for all processed LPAs where returnUnpaid is not true
                    "The donor and all attorneys on the LPA will get a letter telling them the outcome",

                    // link to Use an LPA
                    "the donor and attorneys will be able to use it",
                    "by creating an account on",
                    "Use an LPA",
                    "and adding the details provided in the letter",
                ],
            ],
        ],

        // rejected LPA displays as "Processed"
        // with rejection date + 15 working days as expected receipt date
        [
            'lpaId' => '88668805824',
            'lpaMetadata' => [
                'application-receipt-date' => '2021-02-11',
                'application-rejected-date' => '2021-02-14',
                'application-status-date' => '2021-02-14',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'processed',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'processed',
                'stepsDone' => ['waiting', 'received', 'checking'],
                'receiptDate' => '05/03/21',
            ],
        ],

        // withdrawn LPA displays as "Processed"
        // with withdrawn date + 15 working days as expected receipt date
        [
            'lpaId' => '43476377885',
            'lpaMetadata' => [
                'application-receipt-date' => '2020-05-01',
                'application-withdrawn-date' => '2020-05-06',
                'application-status-date' => '2020-05-06',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'processed',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'processed',
                'stepsDone' => ['waiting', 'received', 'checking'],
                'receiptDate' => '27/05/20',
            ],
        ],

        // invalid LPA displays as "Processed"
        // with invalid date + 15 working days as expected receipt date
        [
            'lpaId' => '93348314693',
            'lpaMetadata' => [
                'application-receipt-date' => '2021-01-02',
                'application-invalid-date' => '2021-01-05',
                'application-status-date' => '2021-01-05',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'processed',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'processed',
                'stepsDone' => ['waiting', 'received', 'checking'],
                'receiptDate' => '26/01/21',
            ],
        ],

        // LPA marked as processed but without any useful dates should display
        // the "15 working days" text
        [
            'lpaId' => '73348314699',
            'lpaMetadata' => [
                'application-receipt-date' => '2021-01-02',
                'application-status-date' => '2021-01-05',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => null,
                'status' => 'processed',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'processed',
                'stepsDone' => ['waiting', 'received', 'checking'],
                'receiptDate' => '15 working days',
            ],
        ],

        // LPA which was "Return - unpaid" status on Sirius should show as "Processed"
        // but not show the text about donor and attorneys getting the LPA letter
        [
            'lpaId' => '15527329531',
            'lpaMetadata' => [
                'application-receipt-date' => '2020-02-27',
                'application-dispatch-date' => '2020-02-27',
                'application-status-date' => '2020-02-27',
            ],
            'lpaStatusDetails' => [
                'found' => true,
                'deleted' => false,
                'returnUnpaid' => true,
                'status' => 'processed',
            ],
            'lpaCanGenerateLPA120' => true,
            'expected' => [
                'status' => 'processed',
                'stepsDone' => ['waiting', 'received', 'checking'],
                'receiptDate' => '19/03/20',
                'notTextFragments' => [
                    "The donor and all attorneys on the LPA will get a letter telling them the outcome"
                ],
            ],
        ],
    ];

    private function buildAndTestViewModel(string|int $index, array $testCase): void
    {
        $lpaId = $testCase['lpaId'];
        $lpaMetadata = $testCase['lpaMetadata'];

        $lpaStatusDetails = [];
        $lpaStatusDetails[$lpaId] = $testCase['lpaStatusDetails'];
        $lpaCanGenerateLPA120 = $testCase['lpaCanGenerateLPA120'];

        $expectedStatus = $testCase['expected']['status'];

        // steps completed (excluding the current step)
        $expectedStepsDone = $testCase['expected']['stepsDone'];

        $expectedReceiptDate = null;
        if (isset($testCase['expected']['receiptDate'])) {
            $expectedReceiptDate = $testCase['expected']['receiptDate'];
        }

        $expectedTextFragments = [];
        if (isset($testCase['expected']['textFragments'])) {
            $expectedTextFragments = $testCase['expected']['textFragments'];
        }

        $notExpectedTextFragments = [];
        if (isset($testCase['expected']['notTextFragments'])) {
            $notExpectedTextFragments = $testCase['expected']['notTextFragments'];
        }

        $lpa = Mockery::mock(Lpa::class);

        $lpa->shouldReceive('getId')->andReturn($lpaId);
        $lpa->shouldReceive('getCompletedAt')->andReturn(new DateTime('now'));
        $lpa->shouldReceive('getMetadata')->andReturn($lpaMetadata);
        $lpa->shouldReceive('canGenerateLPA120')->andReturn($lpaCanGenerateLPA120);

        $lpa->shouldReceive('getDocument')->andReturn($this->document);

        $viewData = $this->builder->build(
            $lpa,
            $lpaStatusDetails,
            $this->trackFromDate,
            $this->expectedWorkingDaysBeforeReceipt,
        );

        $this->assertNotNull(
            $viewData,
            "Builder returned null unexpectedly (test case $index / lpa ID $lpaId)"
        );

        /* For the purposes of the test, we extract the variables from the view
         * model and render just the 'content' block of the detailed status page view.
         * We do this from the Twig template directly, bypassing the ZfcTwig machinery.
         * This replicates what happens in ZfcTwig
         * (see vendor/kokspflanze/zfc-twig/src/View/TwigRenderer.php),
         * but ignores the view itself, layout, most filters and functions, view helpers
         * etc. as far as possible.
         */
        $viewModel = new ViewModel($viewData->toArray());

        // Render
        $html = $this->renderer->render($viewModel, 'content');

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXpath($dom);

        // check progress bar; for each "done" status, we expect to find a progress
        // bar highlight on that step and a tick; if not done, we expect a number
        foreach (self::PROGRESS_BAR_STEPS as $step) {
            $query = "//li[contains(@class, \"progress-bar__steps-$step\")]";

            if (in_array($step, $expectedStepsDone)) {
                // ticked step
                $query .= "/span[@class = \"progress-bar__steps--completed\"]";
            } else {
                // numbered step
                $query .= "/span[@class = \"progress-bar__steps--numbers\"]";
            }

            $matches = $xpath->query($query);
            $this->assertEquals(
                1,
                $matches->length,
                "Could not find progress step element matching '$query' (test case $index / lpa ID $lpaId)"
            );
        }

        // check that the current state is highlighted in the progress bar;
        // it is numbered and has a class to give it a different-coloured background
        $query = "//li[contains(@class, \"current-$expectedStatus\")]";
        $matches = $xpath->query($query);
        $this->assertEquals(
            1,
            $matches->length,
            "Could not find highlighted current step element with '$query' (test case $index / lpa ID $lpaId)"
        );

        // check div is present with current status marked on it
        $query = "//div[@class = \"opg-status--$expectedStatus\"]";
        $matches = $xpath->query($query);
        $this->assertEquals(
            1,
            $matches->length,
            "Could not find div with expected status marker with '$query' (test case $index / lpa ID $lpaId)"
        );

        // check receipt date
        if (!is_null($expectedReceiptDate)) {
            $matches = $xpath->query("//span[contains(text(), \"$expectedReceiptDate\")]");
            $this->assertEquals(
                1,
                $matches->length,
                "Unable to find \"$expectedReceiptDate\" (test case $index / lpa ID $lpaId)"
            );
        }

        // check for text specific to current status
        foreach ($expectedTextFragments as $textFragment) {
            $textFragment = '/' . preg_quote($textFragment) . '/';
            $match = preg_match($textFragment, $html);
            $this->assertEquals(
                1,
                $match,
                "Unable to find \"$textFragment\" (test case $index / lpa ID $lpaId)"
            );
        }

        // check that text is not present for this specific context
        foreach ($notExpectedTextFragments as $textFragment) {
            $matches = $xpath->query("//*[contains(text(), \"$textFragment\")]");
            $this->assertEquals(
                0,
                $matches->length,
                "Found \"$textFragment\" but should not have (test case $index / lpa ID $lpaId)"
            );
        }

        $this->assertTrue(true);
    }

    public function testStatusViewModelHelperBuild(): void
    {
        foreach ($this->testCases as $index => $testCase) {
            if ($index == 0) {
                echo "\n";
            }
            echo "Running tests for StatusViewModelHelper test case $index\n";
            $this->buildAndTestViewModel($index, $testCase);
        }
    }
}
