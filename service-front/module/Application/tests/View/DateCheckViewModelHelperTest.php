<?php

namespace ApplicationTest\View;

use Application\Form\Lpa\DateCheckForm;
use Application\View\Helper\FormElementErrorsV2;
use Application\View\Helper\FormErrorTextExchange;
use Application\View\DateCheckViewModelHelper;
use DOMDocument;
use DOMXpath;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\View\Model\ViewModel;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class DateCheckViewModelHelperTest extends MockeryTestCase
{
    /** @var array */
    /* The keys correspond to the tests that check specific twig blocks in the given template */
    private $templates = [
        'donor' => [
            'block' => 'donorGuidance',
            'path' => 'authenticated/lpa/date-check/index.twig'
        ],
        'attorney' => [
            'block' => 'attorneyGuidance',
            'path' => 'authenticated/lpa/date-check/partials/continuation-note-for-corporation.twig'
        ]
    ];

    private $testCases = [
        // Continuation sheet 1
        // LPA has more than 4 primary attorneys (generates CS1)
        [
            'lpa' => [
                'document' => [
                    'primaryAttorneys' => [
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 1 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],
        // LPA has more than 4 replacement attorneys (generates CS1)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneys' => [
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 1 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],
        // LPA has more than 4 people to notify (generates CS1)
        [
            'lpa' => [
                'document' => [
                    // Empty arrays represent each person, details are missing as they are are unimportant
                    'peopleToNotify' => [[], [], [], [], []]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 1 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],

        // Continuation sheet 2
        // LPA has additional information on how attorneys should act (section 3) (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'primaryAttorneyDecisions' => [
                        'howDetails' => 'Attorneys should only step in when I am say so.'
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'
            ],
            'expectedAttorneyText' => []
        ],
        // LPA has additional information on how replacement attorneys should act (section 4) (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneyDecisions' => [
                        'howDetails' => 'Replacement attorneys should only step in when I say so.'
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],
        // LPA has additional information on when replacement attorneys should act (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneyDecisions' => [
                        'when' => 'When the primary attorney cannot be contacted'
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],
        // LPA has additional information in preferences and instructions (section 7) (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'instruction' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                        incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
                        exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute
                        irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat
                        nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa
                        qui officia deserunt mollit anim id est laborum.'
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'
            ],
            'expectedAttorneyText' => []
        ],
        // Combined CS1 and CS2
        // 1. LPA has more than 4 people to notify (generates CS1)
        // 2. additional information on when replacement attorneys should act (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'peopleToNotify' => [[], [], [], [], []],
                    'replacementAttorneyDecisions' => [
                        'when' => 'When the primary attorney cannot be contacted'
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheets 1 and 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],
        // Continuation sheet 3
        // Health & welfare (HW) LPA - donor cannot sign or make a mark
        [
            'lpa' => [
                'document' => [
                    'type' => 'health-and-welfare',
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => [
                'This person must have signed continuation sheet 3 on the same day as they sign ' .
                'section 5 and before the certificate provider signs section 10.',
            ],
            'expectedAttorneyText' => []
        ],
        // Property & finance (PF) LPA - donor cannot sign or make a mark
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => ['This person must have signed continuation sheet 3 before the certificate ' .
                                    'provider has signed section 10.'],
            'expectedAttorneyText' => []
        ],
        // Continuation sheet 4
        // Property & finance LPA - primary attorney is a trust corporation
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'primaryAttorneys' => [
                        ['type' => 'corporation', 'number' => '123']
                    ]
                ]
            ],
            'expectedDonorText' => [],
            'expectedAttorneyText' => ['They must have signed continuation sheet 4 after the ' .
                                       '\'certificate provider\' has signed section 10 of the LPA form.']
        ],
        // Combined continuation sheet scenarios
        // CS3 & CS1 PF LPA - donor cannot sign or make a mark, >4 people to notify
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'peopleToNotify' => [[], [], [], [], []],
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => [
                'Continuation sheet 1 must have been signed and dated before or on the ' .
                'same day as they signed continuation sheet 3.',
            ],
            'expectedAttorneyText' => []
        ],
        // CS3 & CS2 PF LPA - donor cannot sign or make a mark, additional info on how attorneys make decisions
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'replacementAttorneyDecisions' => [
                        'howDetails' => 'Replacement attorneys should only step in when I say so.'
                    ],
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => ['Continuation sheet 2 must have been signed and dated before or on the ' .
                                    'same day as they signed continuation sheet 3.'],
            'expectedAttorneyText' => []
        ],
        // CS3 & CS2 & CS1 PF LPA - donor cannot sign or make a mark,
        // additional info on how attorneys make decisions, >4 people to notify
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'peopleToNotify' => [[], [], [], [], []],
                    'replacementAttorneyDecisions' => [
                        'howDetails' => 'Replacement attorneys should only step in when I say so.'
                    ],
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => ['Continuation sheets 1 and 2 must have been signed and dated before or on the ' .
                                    'same day as they signed continuation sheet 3.'],
            'expectedAttorneyText' => []
        ],
        // CS3 & CS1 HW LPA - donor cannot sign or make a mark, >4 people to notify
        [
            'lpa' => [
                'document' => [
                    'type' => 'health-and-welfare',
                    'peopleToNotify' => [[], [], [], [], []],
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => ['Continuation sheet 1 must have been signed and dated before or on the ' .
                                    'same day as they signed section 5.',
                                    'Section 5 must have been signed and ' .
                                    'dated before or on the same day as they signed continuation sheet 3.'],
            'expectedAttorneyText' => []
        ],
        // CS3 & CS2 HW LPA - donor cannot sign or make a mark, additional info on how attorneys make decisions
        [
            'lpa' => [
                'document' => [
                    'type' => 'health-and-welfare',
                    'replacementAttorneyDecisions' => [
                        'howDetails' => 'Replacement attorneys should only step in when I say so.'
                    ],
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => ['Continuation sheet 2 must have been signed and dated before or on the ' .
                                    'same day as they signed section 5.',
                                    'Section 5 must have been signed and ' .
                                    'dated before or on the same day as they signed continuation sheet 3.'],
            'expectedAttorneyText' => []
        ],
        // CS3 & CS2 HW LPA - donor cannot sign or make a mark, >4 people to notify
        [
            'lpa' => [
                'document' => [
                    'type' => 'health-and-welfare',
                    'peopleToNotify' => [[], [], [], [], []],
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => ['Continuation sheet 1 must have been signed and dated before or on the ' .
                                    'same day as they signed section 5.',
                                    'Section 5 must have been signed and ' .
                                    'dated before or on the same day as they signed continuation sheet 3.'],
            'expectedAttorneyText' => []
        ],
        // CS3 & CS2 & CS1 HW LPA - donor cannot sign or make a mark,
        // additional info on how attorneys make decisions, >4 people to notify
        [
            'lpa' => [
                'document' => [
                    'type' => 'health-and-welfare',
                    'peopleToNotify' => [[], [], [], [], []],
                    'replacementAttorneyDecisions' => [
                        'howDetails' => 'Replacement attorneys should only step in when I say so.'
                    ],
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => ['Continuation sheets 1 and 2 must have been signed and dated before or on the ' .
                                    'same day as they signed section 5.',
                                    'Section 5 must have been signed and ' .
                                    'dated before or on the same day as they signed continuation sheet 3.'],
            'expectedAttorneyText' => []
        ],
    ];

    /* For the purposes of the test, we extract the variables from the view
     * model and render just the specified template block of the date check tool page view.
     * We do this from the Twig template directly, bypassing the ZfcTwig machinery.
     * This replicates what happens in ZfcTwig
     * (see vendor/kokspflanze/zfc-twig/src/View/TwigRenderer.php),
     * but ignores the view itself, layout, most filters and functions, view helpers
     * etc. as far as possible.
     */
    private function renderViewModel(Lpa $lpa, string $templateName, array $attorneys = []): string
    {
        $viewModel = new ViewModel([
            'returnRoute' => 'lpa/complete',
            'lpa' => $lpa,
            'attorney' => reset($attorneys)
            // using reset() to get 0th element as indexing does not work. Note that the template is
            // only rendered and tested once for the first attorney in each test
        ]);

        // call helper under test
        $helperResult = DateCheckViewModelHelper::build($lpa);

        // set vars on ViewModel as it is done in controller
        $viewModel->setVariables([
            'continuationSheets' => $helperResult['continuationSheets'],
            'applicants' => []
        ]);

        $renderer = new Environment(
            new FilesystemLoader('module/Application/view/application'),
            ['cache' => 'build/twig-cache']
        );

        // This returns an arbitrary string to imitate real twig functions added to the
        // renderer before it tries to render a template.
        $noop = function () {
            return 'Noop';
        };

        $renderer->addFunction(new TwigFunction('formElementErrorsV2', $noop));
        $renderer->addFunction(new TwigFunction('form', $noop));
        $renderer->addFunction(new TwigFunction('formErrorTextExchange', $noop));
        $renderer->addFunction(new TwigFunction('formElement', $noop));

        $template = $renderer->load($this->templates[$templateName]['path']);

        $vars = (array) $viewModel->getVariables();
        $html = $template->renderBlock($this->templates[$templateName]['block'], $vars);

        return $html;
    }

    private function findHtmlMatches(
        string $html,
        string $selector = "//p[@data-cy='continuation-sheet-info']"
    ): array {
        $dom = new DOMDocument();

        if (empty(trim($html))) {
            return [];
        }

        $dom->loadHTML($html);
        $xpath = new DOMXpath($dom);
        $matches = $xpath->query($selector);

        $matchesArray = [];
        foreach ($matches as $match) {
            array_push($matchesArray, $match->nodeValue);
        }

        return $matchesArray;
    }

    public function testDateCheckViewModelHelperDonorGuidance(): void
    {
        foreach ($this->testCases as $index => $testCase) {
            $lpa = new Lpa($testCase['lpa']);

            // render and check HTML for matches
            $html = $this->renderViewModel($lpa, 'donor');
            $matchesArray = $this->findHtmlMatches($html);

            $expectedText = $testCase['expectedDonorText'];

            if ($index == 0) {
                echo "\n";
            }
            echo "Running tests for donor DateCheckViewModelHelper test case $index\n";

            $this->assertEquals(
                $matchesArray,
                $expectedText,
            );
        }
    }

    // LPAL-875
    // test specifically for text next to the donor signing date boxes,
    // when the donor cannot sign and we get a CS3 which is also signed by
    // two witnesses
    public function testDateCheckViewModelHelperDonorCannotSignWitnesses(): void
    {
        $lpa = new Lpa([
            'document' => [
                'type' => 'property-and-financial',
                'donor' => [
                    'canSign' => false
                ]
            ]
        ]);

        $html = $this->renderViewModel($lpa, 'donor');
        $matchesArray = $this->findHtmlMatches($html, "//p[@data-cy='donor-check-signature-date-prompt']");

        $this->assertEquals(
            trim($matchesArray[0]),
            'This person signed continuation sheet 3 on behalf of the donor, followed by two witnesses, on',
        );
    }

    public function testDateCheckViewModelHelperAttorneyGuidance(): void
    {
        foreach ($this->testCases as $index => $testCase) {
            $lpa = new Lpa($testCase['lpa']);

            // If testcase lpa doesn't have primary attorneys, render the repl attorney instead
            $attorneys = $lpa->document->primaryAttorneys;
            if (empty($lpa->document->primaryAttorneys)) {
                $attorneys = $lpa->document->replacementAttorneys;
            }

            // render and check HTML for matches
            $html = $this->renderViewModel($lpa, 'attorney', $attorneys);
            $matchesArray = $this->findHtmlMatches($html);

            $expectedText = $testCase['expectedAttorneyText'];

            if ($index == 0) {
                echo "\n";
            }
            echo "Running tests for attorney DateCheckViewModelHelper test case $index\n";

            $this->assertEquals(
                $matchesArray,
                $expectedText,
            );
        }
    }
}
