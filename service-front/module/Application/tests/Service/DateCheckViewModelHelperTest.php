<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Service\DateCheckViewModelHelper;
use ApplicationTest\View\ViewModelRenderer;
use DOMDocument;
use DOMXpath;
use Laminas\View\Model\ViewModel;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;

final class DateCheckViewModelHelperTest extends MockeryTestCase
{
    private ViewModelRenderer $renderer;

    public function setUp(): void
    {
        $this->renderer = new ViewModelRenderer();
        $this->renderer->addFunction('formElementErrorsV2');
        $this->renderer->addFunction('form');
        $this->renderer->addFunction('formErrorTextExchange');
        $this->renderer->addFunction('formElement');

        $this->dateCheckViewModelHelper = new DateCheckViewModelHelper(
            new ContinuationSheets()
        );
    }

    /* The keys correspond to the tests that check specific twig blocks in the given template */
    private array $templates = [
        'donor' => [
            'block' => 'donorGuidance',
            'path' => 'application/authenticated/lpa/date-check/index.twig'
        ],
        'attorney' => [
            'block' => 'attorneyGuidance',
            'path' => 'application/authenticated/lpa/date-check/partials/continuation-note-for-corporation.twig'
        ]
    ];

    // test cases for cs2 reference criteria used in the ContinuationSheets class to determine
    // whether a cs2 will be produced for a particular LPA
    private array $testCases = [
        // Continuation sheet 1
        // 0. LPA has more than 4 primary attorneys (generates CS1)
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
        // 1. LPA has more than 4 replacement attorneys (generates CS1)
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
        // 2. LPA has more than 4 people to notify (generates CS1)
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
        // 3. LPA has additional information on how attorneys should act (section 3) (generates CS2)
        // ($multiplePas && $paHow == $jointSomeJointSevOther && $zeroRas)
        [
            'lpa' => [
                'document' => [
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
                    ],
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'
            ],
            'expectedAttorneyText' => []
        ],
        // 4. LPA has additional information on how replacement attorneys should act (section 4) (generates CS2)
        // ($singlePa && $multipleRas && $raHow == $jointSomeJointSevOther)
        [
            'lpa' => [
                'document' => [
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],
        // 5. LPA has additional information on when replacement attorneys should act (generates CS2)
        // ($multiplePas && $paHow == $jointSev && $singleRa && $raWhen == $whenOther)
        [
            'lpa' => [
                'document' => [
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    ],
                    'replacementAttorneyDecisions' => [
                        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],
        // 6. LPA has additional information in preferences and instructions (section 7) (generates CS2)
        // long instruction or preference
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
        // 7. Combined CS1 and CS2
        //   1. LPA has more than 4 people to notify (generates CS1)
        //   2. additional information on when replacement attorneys should act (generates CS2)
        // ($multiplePas && $paHow == $jointSev && $multipleRas && $raWhen == $whenOther)
        [
            'lpa' => [
                'document' => [
                    'peopleToNotify' => [[], [], [], [], []],
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
                    ]
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheets 1 and 2 before you signed ' .
                'section 9 of the LPA, or on the same day.'],
            'expectedAttorneyText' => []
        ],
        // 8. Continuation sheet 3
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
        // 9. Property & finance (PF) LPA - donor cannot sign or make a mark
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
        // 10. Continuation sheet 4
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
        // 11. Combined continuation sheet scenarios
        // CS1 & CS3 PF LPA - donor cannot sign or make a mark, >4 people to notify
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
        // 12. CS2 & CS3 PF LPA - donor cannot sign or make a mark, additional info on how attorneys make decisions
        // ($singlePa && $multipleRas && $raHow == $jointSev)
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
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
        // 13. CS1 & CS2 & CS3 PF LPA - donor cannot sign or make a mark,
        // additional info on how attorneys make decisions, >4 people to notify
        // ($multiplePas && $paHow == $joint && $multipleRas && $raHow == $jointSev)
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'peopleToNotify' => [[], [], [], [], []],
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
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
        // 14. CS1 & CS3 HW LPA - donor cannot sign or make a mark, >4 people to notify
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
        // 15. CS2 & CS3 HW LPA - donor cannot sign or make a mark, additional info on how attorneys make decisions
        // ($multiplePas && $paHow == $joint && $multipleRas && $raHow = $jointSomeJointSevOther)
        [
            'lpa' => [
                'document' => [
                    'type' => 'health-and-welfare',
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
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
        // 16. CS1 & CS3 HW LPA - donor cannot sign or make a mark, >4 people to notify
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
        // 17. CS1 & CS2 & CS3 HW LPA - donor cannot sign or make a mark,
        // additional info on how attorneys make decisions, >4 people to notify
        // ($multiplePas && $paHow == $jointSev && $singleRa && $raWhen == $whenNone)
        [
            'lpa' => [
                'document' => [
                    'type' => 'health-and-welfare',
                    'peopleToNotify' => [[], [], [], [], []],
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
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
        // 18. CS2 PF LPA
        // $multiplePas && $paHow == $joint && $multipleRas && $raHow == $jointSomeJointSevOther
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
                    ],
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed section 9 of the LPA, ' .
                'or on the same day.'
            ],
            'expectedAttorneyText' => []
        ],
        // 19. CS2 PF LPA
        // $multiplePas && $paHow == $jointSev && $multipleRas && $raWhen == $whenNone && $raHow == $jointSev
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    ],
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed section 9 of the LPA, ' .
                'or on the same day.'
            ],
            'expectedAttorneyText' => []
        ],
        // 20. CS2 PF LPA
        // $multiplePas && $paHow == $jointSev && $multipleRas &&
        // $raWhen == $whenNone && $raHow == $jointSomeJointSevOther
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'replacementAttorneyDecisions' => [
                        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
                        'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
                    ],
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed section 9 of the LPA, ' .
                'or on the same day.'
            ],
            'expectedAttorneyText' => []
        ],
        // 21. CS2 PF LPA
        // $multiplePas && $paHow == $jointSomeJointSevOther && $singleRa
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                    ],
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed section 9 of the LPA, ' .
                'or on the same day.'
            ],
            'expectedAttorneyText' => []
        ],
        // 22. CS2 PF LPA
        // $multiplePas && $paHow == $jointSomeJointSevOther && $multipleRas
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                    'primaryAttorneyDecisions' => [
                        'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
                    ],
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                    ],
                ]
            ],
            'expectedDonorText' => [
                'You must have signed and dated continuation sheet/s 2 before you signed section 9 of the LPA, ' .
                'or on the same day.'
            ],
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

        $this->renderer->loadTemplate($this->templates[$templateName]['path']);
        return $this->renderer->render($viewModel, $this->templates[$templateName]['block']);
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

            $this->assertEquals(
                $expectedText,
                $matchesArray,
                "Test case $index - Unable to find text: " . print_r($expectedText, true),
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

            $this->assertEquals(
                $expectedText,
                $matchesArray,
            );
        }
    }
}
