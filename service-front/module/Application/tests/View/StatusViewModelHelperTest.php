<?php

namespace ApplicationTest\View;

use Application\View\StatusViewModelHelper;
use Application\View\Helper\FormatLpaId;
use DateTime;
use DOMDocument;
use DOMXpath;
use Laminas\View\Model\ViewModel;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\TwigFunction;

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
class StatusViewModelHelperTest extends MockeryTestCase
{
    /** @var DateTime */
    private $trackFromDate;

    /** @var int */
    private $expectedWorkingDaysBeforeReceipt = 15;

    /** @var Document */
    private $document;

    /** @var array */
    private const PROGRESS_BAR_STEPS = ['waiting', 'received', 'checking', 'processed'];

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
    }

    // TODO this will be loaded from a JSON file eventually
    private $testCases = [
        // deleted from Sirius
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
                'receiptDate' => null,
                'textFragments' => [
                    "We're waiting to receive the LPA",
                    "we'll write to Ms Mecnodo Expodo",
                    "we've heard back from Ms Mecnodo Expodo"
                ]
            ],
        ],

        // registered and dispatched
        [
            'lpaId' => '32004638272',
            'lpaMetadata' => [
                'application-receipt-date' => '2021-05-01',
                'application-dispatch-date' => '2021-05-03',
                'application-registration-date' => '2021-05-02',
                'application-status-date' => '2021-05-03',
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
                    "Ms Mecnodo Expodo will receive the LPA in the post",
                ]
            ],
        ],
    ];

    /* For the purposes of the test, we extract the variables from the view
     * model and render just the 'content' block of the detailed status page view.
     * We do this from the Twig template directly, bypassing the ZfcTwig machinery.
     * This replicates what happens in ZfcTwig
     * (see vendor/kokspflanze/zfc-twig/src/View/TwigRenderer.php),
     * but ignores the view itself, layout, most filters and functions, view helpers
     * etc. as far as possible.
     */
    private function renderViewModel(?ViewModel $viewModel): string
    {
        $loader = new FilesystemLoader('module/Application/view/application');

        $renderer = new Environment($loader);
        $renderer->addFunction(new TwigFunction('formatLpaId', FormatLpaId::class));

        $template = $renderer->load('authenticated/lpa/status/index.twig');

        $vars = (array) $viewModel->getVariables();
        return $template->renderBlock('content', $vars);
    }

    private function buildAndTestViewModel($index, $testCase)
    {
        $lpaId = $testCase['lpaId'];
        $lpaMetadata = $testCase['lpaMetadata'];

        $lpaStatusDetails = [];
        $lpaStatusDetails[$lpaId] = $testCase['lpaStatusDetails'];
        $lpaCanGenerateLPA120 = $testCase['lpaCanGenerateLPA120'];

        $expectedStatus = $testCase['expected']['status'];

        // steps completed (excluding the current step)
        $expectedStepsDone = $testCase['expected']['stepsDone'];

        $expectedReceiptDate = $testCase['expected']['receiptDate'];
        $expectedTextFragments = $testCase['expected']['textFragments'];

        $lpa = Mockery::mock(Lpa::class);

        $lpa->shouldReceive('getId')->andReturn($lpaId);
        $lpa->shouldReceive('getCompletedAt')->andReturn(new DateTime('now'));
        $lpa->shouldReceive('getMetadata')->andReturn($lpaMetadata);
        $lpa->shouldReceive('canGenerateLPA120')->andReturn($lpaCanGenerateLPA120);

        $lpa->shouldReceive('getDocument')->andReturn($this->document);

        // method we're testing
        $viewModel = StatusViewModelHelper::build(
            $lpa,
            $lpaStatusDetails,
            $this->trackFromDate,
            $this->expectedWorkingDaysBeforeReceipt,
        );

        // render and check the HTML
        $html = $this->renderViewModel($viewModel);

        echo $html;

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXpath($dom);

        // check progress bar; for each "done" status, we expect to find a progress
        // bar highlight on that step and no number; if not done, we expect a number
        foreach (self::PROGRESS_BAR_STEPS as $step) {
            $query = "//li[contains(@class, \"progress-bar__steps-$step\")]";

            if (in_array($step, $expectedStepsDone)) {
                $query .= "/span[@class = \"progress-bar__steps--completed\"]";
            } else {
                $query .= "/span[@class = \"progress-bar__steps--numbers\"]";
            }

            $matches = $xpath->query($query);
            $this->assertEquals(
                1,
                $matches->length,
                "Could not find progress step element matching '$query'"
            );
        }

        // check that the current state is also highlighted in the progress bar;
        // it is also numbered rather than just filled
        $query = "//li[contains(@class, \"current-$expectedStatus\")]";
        $matches = $xpath->query($query);
        $this->assertEquals(
            1,
            $matches->length,
            "Could not find highlighted current step element with '$query'"
        );

        // check div is present
        $query = "//div[@class = \"opg-status--$expectedStatus\"]";
        $matches = $xpath->query($query);
        $this->assertEquals(
            1,
            $matches->length,
            "Could not find div with expected status marker with '$query'"
        );

        // check receipt date
        if (!is_null($expectedReceiptDate)) {
            $matches = $xpath->query("//span[contains(text(), \"$expectedReceiptDate\")]");
            $this->assertEquals(
                1,
                $matches->length,
                "Unable to find \"$expectedReceiptDate\" (test case $index)"
            );
        }

        // check for text specific to this current status
        foreach ($expectedTextFragments as $textFragment) {
            $matches = $xpath->query("//*[contains(text(), \"$textFragment\")]");
            $this->assertGreaterThan(
                0,
                $matches->length,
                "Unable to find \"$textFragment\" (test case $index)"
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
