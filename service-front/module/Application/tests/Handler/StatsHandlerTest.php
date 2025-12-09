<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\StatsHandler;
use Application\Model\Service\Stats\Stats as StatsService;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class StatsHandlerTest extends MockeryTestCase
{
    public function testHandle(): void
    {
        $statsService = Mockery::mock(StatsService::class);
        $statsService->shouldReceive('getApiStats')->andReturn($this->getApiStats())->once();

        $twigRenderer = Mockery::mock(TemplateRendererInterface::class);

        $twigRenderer
            ->shouldReceive('render')
            ->once()
            ->with(
                'application/general/stats',
                    ['generated' => '01/02/2017 14:22:11',
                    'lpas' => $this->getLpaStats(),
                    'who' => $this->getWhoAreYouStats(),
                    'users' => $this->getAuthStats(),
                    'correspondence' => $this->getCorrespondenceStats(),
                    'preferencesInstructions' => $this->getPreferencesInstructionsStats(),
                ]
            )
            ->andReturn('<html>stats page</html>');

        $handler = new StatsHandler($statsService, $twigRenderer);
        $result = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $result);
    }

    private function getApiStats(): array
    {
        return [
            'generated'               => '01/02/2017 14:22:11',
            'lpas'                    => $this->getLpaStats(),
            'who'                     => $this->getWhoAreYouStats(),
            'correspondence'          => $this->getCorrespondenceStats(),
            'preferencesInstructions' => $this->getPreferencesInstructionsStats(),
            'users'                   => $this->getAuthStats(),
        ];
    }

    private function getLpaStats(): array
    {
        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $byMonth = [];
        for ($i = 1; $i <= 4; $i++) {
            $byMonth[date('Y-m', $start->getTimestamp())] = [
                'started' => 1,
                'created' => 1,
                'completed' => 1
            ];

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        return [
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
    }

    private function getAuthStats(): array
    {
        return [
            'total' => 1,
            'activated' => 1,
            'activated-this-month' => 1,
            'deleted' => 1,
        ];
    }

    private function getWhoAreYouStats(): array
    {
        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $byMonth = [];
        for ($i = 1; $i <= 4; $i++) {
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

        return [
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
    }

    /**
     * @return mixed[]
     */
    private function getCorrespondenceStats(): array
    {
        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $stats = [];
        for ($i = 1; $i <= 4; $i++) {
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

    /**
     * @return mixed[]
     */
    private function getPreferencesInstructionsStats(): array
    {
        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $stats = [];

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
