<?php

namespace Application\Model\Service\System;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\Stats\StatsRepositoryTrait;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryTrait;
use Application\Model\Service\AbstractService;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use DateTime;
use Exception;
use MakeShared\Logging\LoggerTrait;

/**
 * Generate LPA stats and saves the results back into the Database.
 * To run, bash into the api-app container, cd to /app and run
 * './vendor/bin/laminas service-api:generate-stats'
 *
 * Class Stats
 * @package Application\Model\Service\System
 */
class Stats extends AbstractService
{
    use ApplicationRepositoryTrait;
    use StatsRepositoryTrait;
    use WhoRepositoryTrait;
    use LoggerTrait;

    /**
     * @return bool
     */
    public function generate(): bool
    {
        $stats = [];

        $startGeneration = microtime(true);

        try {
            $stats['lpas'] = $this->getLpaStats();
            $this->getLogger()->debug('Successfully generated lpas stats', [
               'userId' => $this->getUserId()
            ]);
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to get LPA status for PDF', [
                'userId' => $this->getUserId(),
                'error_code' => 'LPA_STATS_GENERATION_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
            $stats['lpas'] = ['generated' => false];
        }

        try {
            $stats['lpasPerUser'] = [
                'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
                'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000.0),
                'all' => $this->getApplicationRepository()->getLpasPerUser(),
            ];

            $this->getLogger()->debug('Successfully generated lpasPerUser stats', [
                'userId' => $this->getUserId()
            ]);
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to get LPAs per user for PDF', [
                'userId' => $this->getUserId(),
                'error_code' => 'LPAS_PER_USER_STATS_GENERATION_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            $stats['lpasPerUser'] = ['generated' => false];
        }

        try {
            $stats['who'] = $this->getWhoAreYou();
            $this->getLogger()->debug('Successfully generated who stats');
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to get Who Are You Stats for PDF', [
                'userId' => $this->getUserId(),
                'error_code' => 'WHO_ARE_YOU_STATS_GENERATION_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
            $stats['who'] = ['generated' => false];
        }

        try {
            $stats['correspondence'] = $this->getCorrespondenceStats();
            $this->getLogger()->debug('Successfully generated correspondence stats for PDF');
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to get Correspondence Stats for PDF', [
                'userId' => $this->getUserId(),
                'error_code' => 'CORRESPONDENCE_STATS_GENERATION_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            $stats['correspondence'] = ['generated' => false];
        }

        try {
            $stats['preferencesInstructions'] = $this->getPreferencesInstructionsStats();
            $this->getLogger()->debug('Successfully generated preferencesInstructions stats');
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to generate preferences instructions stats for PDF', [
                'userId' => $this->getUserId(),
                'error_code' => 'PREFERENCES_INSTRUCTIONS_STATS_GENERATION_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            $stats['preferencesInstructions'] = ['generated' => false];
        }

        try {
            $stats['options'] = $this->getOptionsStats();
            $this->getLogger()->debug('Successfully generated options stats');
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to generate options stats', [
                'userId' => $this->getUserId(),
                'error_code' => 'OPTIONS_STATS_GENERATION_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            $stats['options'] = ['generated' => false];
        }

        $stats['generated'] = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());
        $stats['generationTimeInMs'] = round((microtime(true) - $startGeneration) * 1000.0);

        //---------------------------------------------------
        // Save the results

        // Empty the collection
        $this->getStatsRepository()->delete();

        // Add the new data
        $this->getStatsRepository()->insert($stats);

        return true;
    }

    /**
     * Return general stats on LPA numbers.
     *
     * Some of this could be done using aggregate queries, however I'd rather keep the queries simple.
     * Stats are not looked at very often, so performance when done like this should be "good enough".
     *
     * @return array
     */
    private function getLpaStats()
    {
        $startGeneration = microtime(true);

        // Broken down by month
        $byMonth = [];

        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        // Go back 4 months...
        for ($i = 1; $i <= 4; $i++) {
            $month = [];

            // Started if we have a startedAt, but no createdAt...
            $month['started'] = $this->getApplicationRepository()->countBetween($start, $end, 'startedAt');

            // Created if we have a createdAt, but no completedAt...
            $month['created'] = $this->getApplicationRepository()->countBetween($start, $end, 'createdAt');

            // Count all the LPAs that have a completedAt...
            $month['completed'] = $this->getApplicationRepository()->countBetween($start, $end, 'completedAt');

            // Count all the LPAs has a receipt date...
            $month['waiting'] = $this->getApplicationRepository()->countWaitingBetween($start, $end);

            // Count all the LPAs has a receipt date...
            $month['received'] = $this->getApplicationRepository()->countReceivedBetween($start, $end);

            // Count all the LPAs has a registration date...
            $month['checking'] = $this->getApplicationRepository()->countCheckedBetween($start, $end);

            // Count all the LPAs that have rejected date...
            $month['processed'] = $this->getApplicationRepository()->countReturnedBetween($start, $end);

            $byMonth[date('Y-m', $start->getTimestamp())] = $month;

            // Modify dates, going back on month...
            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        $summary = [];

        // Broken down by type
        $pf = [];

        // Started if we have a startedAt, but no createdAt...
        $summary['started'] = $pf['started'] =
            $this->getApplicationRepository()->countStartedForType(Document::LPA_TYPE_PF);

        // Created if we have a createdAt, but no completedAt...
        $summary['created'] = $pf['created'] =
            $this->getApplicationRepository()->countCreatedForType(Document::LPA_TYPE_PF);

        // Count all the LPAs that have been received
        $summary['waiting'] = $pf['waiting'] =
            $this->getApplicationRepository()->countWaitingForType(Document::LPA_TYPE_PF);

        // Count all the LPAs that have been received
        $summary['checking'] = $pf['checking'] =
            $this->getApplicationRepository()->countCheckingForType(Document::LPA_TYPE_PF);

        // Count all the LPAs that have been received
        $summary['received'] = $pf['received'] =
            $this->getApplicationRepository()->countReceivedForType(Document::LPA_TYPE_PF);

        // Count all the LPAs that have been processed
        $summary['processed'] = $pf['processed'] =
            $this->getApplicationRepository()->countProcessedForType(Document::LPA_TYPE_PF);

        // Count all the LPAs that have a completedAt...
        $summary['completed'] = $pf['completed'] =
            $this->getApplicationRepository()->countCompletedForType(Document::LPA_TYPE_PF);

        $hw = [];

        // Started if we have a startedAt, but no createdAt...
        $summary['started'] += $hw['started'] =
            $this->getApplicationRepository()->countStartedForType(Document::LPA_TYPE_HW);

        // Created if we have a createdAt, but no completedAt...
        $summary['created'] += $hw['created'] =
            $this->getApplicationRepository()->countCreatedForType(Document::LPA_TYPE_HW);

        // Count all the LPAs that have been received
        $summary['waiting'] += $hw['waiting'] =
            $this->getApplicationRepository()->countWaitingForType(Document::LPA_TYPE_HW);

        // Count all the LPAs that have been received
        $summary['checking'] += $hw['checking'] =
            $this->getApplicationRepository()->countCheckingForType(Document::LPA_TYPE_HW);

        // Count all the LPAs that have been received
        $summary['received'] += $hw['received'] =
            $this->getApplicationRepository()->countReceivedForType(Document::LPA_TYPE_HW);

        // Count all the LPAs that have been processed
        $summary['processed'] += $hw['processed'] =
            $this->getApplicationRepository()->countProcessedForType(Document::LPA_TYPE_HW);

        // Count all the LPAs that have a completedAt...
        $summary['completed'] += $hw['completed'] =
            $this->getApplicationRepository()->countCompletedForType(Document::LPA_TYPE_HW);

        // Deleted LPAs have no 'document'...
        $summary['deleted'] = $this->getApplicationRepository()->countDeleted();

        ksort($byMonth);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000.0),
            'all' => $summary,
            'health-and-welfare' => $hw,
            'property-and-finance' => $pf,
            'by-month' => $byMonth
        ];
    }

    /**
     * Return a breakdown of the Who Are You stats.
     *
     * @return array
     */
    private function getWhoAreYou()
    {
        $startGeneration = microtime(true);

        $results = [];

        $firstDayOfThisMonth = strtotime('first day of ' . date('F Y'));

        $lastTimestamp = time(); // initially set to now...

        for ($i = 0; $i < 4; $i++) {
            $ts = strtotime("-{$i} months", $firstDayOfThisMonth);

            $results['by-month'][date('Y-m', $ts)] = $this->getWhoRepository()->getStatsForTimeRange(
                new DateTime("@{$ts}"),
                new DateTime("@{$lastTimestamp}"),
                WhoAreYou::options()
            );

            $lastTimestamp = $ts;
        }

        $results['all'] =
            $this->getWhoRepository()->getStatsForTimeRange(new DateTime("@0"), new DateTime(), WhoAreYou::options());

        ksort($results['by-month']);

        $results['generated'] = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());
        $results['generationTimeInMs'] = round((microtime(true) - $startGeneration) * 1000.0);

        return $results;
    }

    /**
     * @return array
     */
    private function getCorrespondenceStats()
    {
        $startGeneration = microtime(true);

        $correspondenceStats = [];

        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        // Go back 4 months...
        for ($i = 1; $i <= 4; $i++) {
            $month = [];

            $month['completed'] = $this->getApplicationRepository()->countCompletedBetween($start, $end);

            $month['contactByEmail'] =
                $this->getApplicationRepository()->countCompletedBetweenCorrespondentEmail($start, $end);

            $month['contactByPhone'] =
                $this->getApplicationRepository()->countCompletedBetweenCorrespondentPhone($start, $end);

            $month['contactByPost'] =
                $this->getApplicationRepository()->countCompletedBetweenCorrespondentPost($start, $end);

            $month['contactInEnglish'] =
                $this->getApplicationRepository()->countCompletedBetweenCorrespondentEnglish($start, $end);

            $month['contactInWelsh'] =
                $this->getApplicationRepository()->countCompletedBetweenCorrespondentWelsh($start, $end);

            $correspondenceStats[date('Y-m', $start->getTimestamp())] = $month;

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        ksort($correspondenceStats);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000.0),
            'by-month' => $correspondenceStats
        ];
    }

    /**
     * @return array
     */
    private function getPreferencesInstructionsStats()
    {
        $startGeneration = microtime(true);

        $preferencesInstructionsStats = [];

        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        // Go back 4 months...
        for ($i = 1; $i <= 4; $i++) {
            $month = [];

            $month['completed'] = $this->getApplicationRepository()->countCompletedBetween($start, $end);

            $month['preferencesStated'] =
                $this->getApplicationRepository()->countCompletedBetweenWithPreferences($start, $end);

            $month['instructionsStated'] =
                $this->getApplicationRepository()->countCompletedBetweenWithInstructions($start, $end);

            $preferencesInstructionsStats[date('Y-m', $start->getTimestamp())] = $month;

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        ksort($preferencesInstructionsStats);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000.0),
            'by-month' => $preferencesInstructionsStats
        ];
    }

    /**
     * @return array
     */
    private function getOptionsStats()
    {
        $startGeneration = microtime(true);

        $optionStats = [];

        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        // Go back 4 months...
        for ($i = 1; $i <= 4; $i++) {
            $month = [];

            $month['completed'] = $this->getApplicationRepository()->countCompletedBetween($start, $end);

            $month['type'] = [
                Document::LPA_TYPE_HW =>
                    $this->getApplicationRepository()->countCompletedBetweenByType($start, $end, Document::LPA_TYPE_HW),
                Document::LPA_TYPE_PF =>
                    $this->getApplicationRepository()->countCompletedBetweenByType($start, $end, Document::LPA_TYPE_PF),
            ];

            $month['canSign'] = [
                'true'  => $this->getApplicationRepository()->countCompletedBetweenByCanSign($start, $end, true),
                'false' => $this->getApplicationRepository()->countCompletedBetweenByCanSign($start, $end, false),
            ];

            $month['replacementAttorneys'] = [
                'yes' =>
                    $this->getApplicationRepository()->countCompletedBetweenHasActors(
                        $start,
                        $end,
                        'replacementAttorneys'
                    ),
                'no' =>
                    $this->getApplicationRepository()->countCompletedBetweenHasNoActors(
                        $start,
                        $end,
                        'replacementAttorneys'
                    ),
                'multiple' =>
                    $this->getApplicationRepository()->countCompletedBetweenHasMultipleActors(
                        $start,
                        $end,
                        'replacementAttorneys'
                    ),
            ];

            $month['peopleToNotify'] = [
                'yes' => $this->getApplicationRepository()->countCompletedBetweenHasActors(
                    $start,
                    $end,
                    'peopleToNotify'
                ),
                'no' => $this->getApplicationRepository()->countCompletedBetweenHasNoActors(
                    $start,
                    $end,
                    'peopleToNotify'
                ),
                'multiple' => $this->getApplicationRepository()->countCompletedBetweenHasMultipleActors(
                    $start,
                    $end,
                    'peopleToNotify'
                ),
            ];

            $month['whoIsRegistering'] = [
                'donor' => $this->getApplicationRepository()->countCompletedBetweenDonorRegistering($start, $end),
                'attorneys' =>
                    $this->getApplicationRepository()->countCompletedBetweenAttorneyRegistering($start, $end),
            ];

            $month['repeatCaseNumber'] = [
                'yes' => $this->getApplicationRepository()->countCompletedBetweenCaseNumber($start, $end, true),
                'no' => $this->getApplicationRepository()->countCompletedBetweenCaseNumber($start, $end, false),
            ];

            $month['payment'] = [
                'reducedFeeReceivesBenefits' => $this->getApplicationRepository()->countCompletedBetweenFeeType(
                    $start,
                    $end,
                    true,
                    true,
                    null,
                    null
                ),
                'reducedFeeUniversalCredit' => $this->getApplicationRepository()->countCompletedBetweenFeeType(
                    $start,
                    $end,
                    false,
                    null,
                    false,
                    true
                ),
                'reducedFeeLowIncome' => $this->getApplicationRepository()->countCompletedBetweenFeeType(
                    $start,
                    $end,
                    false,
                    null,
                    true,
                    false
                ),
                'notApply' => $this->getApplicationRepository()->countCompletedBetweenFeeType(
                    $start,
                    $end,
                    null,
                    null,
                    null,
                    null
                ),
                Payment::PAYMENT_TYPE_CARD => $this->getApplicationRepository()->countCompletedBetweenPaymentType(
                    $start,
                    $end,
                    Payment::PAYMENT_TYPE_CARD
                ),
                Payment::PAYMENT_TYPE_CHEQUE => $this->getApplicationRepository()->countCompletedBetweenPaymentType(
                    $start,
                    $end,
                    Payment::PAYMENT_TYPE_CHEQUE
                ),
            ];

            $month['primaryAttorneys'] = [
                'multiple' => $this->getApplicationRepository()->countCompletedBetweenHasMultipleActors(
                    $start,
                    $end,
                    'primaryAttorneys'
                ),
            ];

            $month['primaryAttorneyDecisions'] = [
                'when' => [
                    PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'primaryAttorneyDecisions',
                            'when',
                            PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW
                        ),
                    PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'primaryAttorneyDecisions',
                            'when',
                            PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY
                        ),
                ],
                'how' => [
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'primaryAttorneyDecisions',
                            'how',
                            AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                        ),
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'primaryAttorneyDecisions',
                            'how',
                            AbstractDecisions::LPA_DECISION_HOW_JOINTLY
                        ),
                    AbstractDecisions::LPA_DECISION_HOW_DEPENDS =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'primaryAttorneyDecisions',
                            'how',
                            AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                        ),
                ],
                'canSustainLife' => [
                    'true' => $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                        $start,
                        $end,
                        'primaryAttorneyDecisions',
                        'canSustainLife',
                        'true'
                    ),
                    'false' => $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                        $start,
                        $end,
                        'primaryAttorneyDecisions',
                        'canSustainLife',
                        'false'
                    ),
                ]
            ];

            $month['replacementAttorneyDecisions'] = [
                'when' => [
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'replacementAttorneyDecisions',
                            'when',
                            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST
                        ),
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'replacementAttorneyDecisions',
                            'when',
                            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST
                        ),
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'replacementAttorneyDecisions',
                            'when',
                            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS
                        ),
                ],
                'how' => [
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY =>
                        $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'replacementAttorneyDecisions',
                            'how',
                            AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                        ),
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY
                        => $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'replacementAttorneyDecisions',
                            'how',
                            AbstractDecisions::LPA_DECISION_HOW_JOINTLY
                        ),
                    AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                        => $this->getApplicationRepository()->countCompletedBetweenWithAttorneyDecisions(
                            $start,
                            $end,
                            'replacementAttorneyDecisions',
                            'how',
                            AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                        ),
                ]
            ];

            $month['trust'] = [
                'primaryAttorneys' => $this->getApplicationRepository()->countCompletedBetweenWithTrust(
                    $start,
                    $end,
                    'primaryAttorneys'
                ),
                'replacementAttorneys' => $this->getApplicationRepository()->countCompletedBetweenWithTrust(
                    $start,
                    $end,
                    'replacementAttorneys'
                ),
            ];

            $month['certificateProviderSkipped'] = [
                'yes' => $this->getApplicationRepository()->countCompletedBetweenCertificateProviderSkipped(
                    $start,
                    $end,
                    true
                ),
                'no' => $this->getApplicationRepository()->countCompletedBetweenCertificateProviderSkipped(
                    $start,
                    $end,
                    false
                ),
            ];

            $optionStats[date('Y-m', $start->getTimestamp())] = $month;

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        ksort($optionStats);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000.0),
            'by-month' => $optionStats
        ];
    }
}
