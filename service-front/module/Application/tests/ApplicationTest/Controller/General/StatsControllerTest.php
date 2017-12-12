<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\StatsController;
use ApplicationTest\Controller\AbstractControllerTest;
use Zend\View\Model\ViewModel;

class StatsControllerTest extends AbstractControllerTest
{
    /**
     * @var StatsController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new StatsController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        $this->lpaApplicationService->shouldReceive('getAuthStats')->andReturn($this->getAuthStats())->once();
        $this->lpaApplicationService->shouldReceive('getApiStats')->andReturn($this->getApiStats())->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->getLpaStats(), $result->getVariable('lpas'));
        $this->assertEquals($this->getWhoAreYouStats(), $result->getVariable('who'));
        $this->assertEquals($this->getAuthStats(), $result->getVariable('users'));
        $this->assertEquals($this->getCorrespondenceStats(), $result->getVariable('correspondence'));
        $this->assertEquals($this->getPreferencesInstructionsStats(), $result->getVariable('preferencesInstructions'));
    }

    private function getApiStats()
    {
        return [
            'generated'               => '01/02/2017 14:22:11',
            'lpas'                    => $this->getLpaStats(),
            'who'                     => $this->getWhoAreYouStats(),
            'correspondence'          => $this->getCorrespondenceStats(),
            'preferencesInstructions' => $this->getPreferencesInstructionsStats()
        ];
    }

    private function getLpaStats()
    {
        $start = new \DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $byMonth = array();
        for ($i = 1; $i <=4; $i++) {
            $byMonth[date('Y-m', $start->getTimestamp())] = [
                'started' => 1,
                'created' => 1,
                'completed' => 1
            ];

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        $stats = [
            'all' => [
                'started' => 2,
                'created' => 2,
                'completed' => 2,
                'deleted' => 1
            ],
            'health-and-welfare' => [
                'started' => 1,
                'created' => 1,
                'completed' => 1
            ],
            'property-and-finance' => [
                'started' => 1,
                'created' => 1,
                'completed' => 1
            ],
            'by-month' => $byMonth
        ];

        return $stats;
    }

    private function getAuthStats()
    {
        return [
            'total' => 1,
            'activated' => 1,
            'activated-this-month' => 1,
            'deleted' => 1,
        ];
    }

    private function getWhoAreYouStats()
    {
        $start = new \DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $byMonth = array();
        for ($i = 1; $i <=4; $i++) {
            $byMonth[date('Y-m', $start->getTimestamp())] = [
                'professional' => [
                    'count' => 1,
                    'subquestions' => [
                        'solicitor' => 1,
                        'will-writer' => 1,
                        'other' => 1
                    ]
                ],
                'digitalPartner' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'organisation' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'donor' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'friendOrFamily' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'notSaid' => [
                    'count' => 1,
                    'subquestions' => []
                ],
            ];

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        $stats = [
            'all' => [
                'professional' => [
                    'count' => 1,
                    'subquestions' => [
                        'solicitor' => 1,
                        'will-writer' => 1,
                        'other' => 1
                    ]
                ],
                'digitalPartner' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'organisation' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'donor' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'friendOrFamily' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'notSaid' => [
                    'count' => 1,
                    'subquestions' => []
                ],
            ],
            'by-month' => $byMonth
        ];

        return $stats;
    }

    private function getCorrespondenceStats()
    {
        $start = new \DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $stats = array();
        for ($i = 1; $i <=4; $i++) {
            $stats[date('Y-m', $start->getTimestamp())] = [
                'completed' => 1,
                'contactByEmail' => 1,
                'contactByPhone' => 1,
                'contactByPost' => 1,
                'contactInEnglish' => 1,
                'contactInWelsh' => 1
            ];

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        return $stats;
    }

    private function getPreferencesInstructionsStats()
    {
        $start = new \DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $stats = array();

        for ($i = 1; $i <= 4; $i++) {
            $stats[date('Y-m', $start->getTimestamp())] = [
                'completed' => 1,
                'preferencesStated' => 1,
                'instructionsStated' => 1
            ];

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        return $stats;
    }
}
