<?php

namespace Application\Model\Service\System;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollectionTrait;
use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollectionTrait;
use Application\Model\DataAccess\Mongo\Collection\ApiWhoCollectionTrait;
use Application\Model\Service\AbstractService;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use DateTime;
use Exception;

/**
 * Generate LPA stats and saves the results back into MongoDB.
 * To run, bash into apiv2, cd to app and run 'php public/index.php generate-stats'
 *
 * Class Stats
 * @package Application\Model\Service\System
 */
class Stats extends AbstractService
{
    use ApiLpaCollectionTrait;
    use ApiStatsLpasCollectionTrait;
    use ApiWhoCollectionTrait;

    /**
     * @return bool
     */
    public function generate()
    {
        $stats = [];

        $startGeneration = microtime(true);

        try {
            $stats['lpas'] = $this->getLpaStats();
            $this->getLogger()->info("Successfully generated lpas stats");
        } catch (Exception $ex) {
            $this->getLogger()->err("Failed to generate lpas stats due to {$ex->getMessage()}", [$ex]);
            $stats['lpas'] = ['generated' => false];
        }

        try {
            $stats['lpasPerUser'] = [
                'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
                'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000),
                'all' => $this->apiLpaCollection->getLpasPerUser(),
            ];

            $this->getLogger()->info("Successfully generated lpasPerUser stats");
        } catch (Exception $ex) {
            $this->getLogger()->err("Failed to generate lpasPerUser stats due to {$ex->getMessage()}", [$ex]);
            $stats['lpasPerUser'] = ['generated' => false];
        }

        try {
            $stats['who'] = $this->getWhoAreYou();
            $this->getLogger()->info("Successfully generated who stats");
        } catch (Exception $ex) {
            $this->getLogger()->err("Failed to generate who stats due to {$ex->getMessage()}", [$ex]);
            $stats['who'] = ['generated' => false];
        }

        try {
            $stats['correspondence'] = $this->getCorrespondenceStats();
            $this->getLogger()->info("Successfully generated correspondence stats");
        } catch (Exception $ex) {
            $this->getLogger()->err("Failed to generate correspondence stats due to {$ex->getMessage()}", [$ex]);
            $stats['correspondence'] = ['generated' => false];
        }

        try {
            $stats['preferencesInstructions'] = $this->getPreferencesInstructionsStats();
            $this->getLogger()->info("Successfully generated preferencesInstructions stats");
        } catch (Exception $ex) {
            $this->getLogger()->err(
                "Failed to generate preferencesInstructions stats due to {$ex->getMessage()}",
                [$ex]
            );
            $stats['preferencesInstructions'] = ['generated' => false];
        }

        try {
            $stats['options'] = $this->getOptionsStats();
            $this->getLogger()->info("Successfully generated options stats");
        } catch (Exception $ex) {
            $this->getLogger()->err("Failed to generate options stats due to {$ex->getMessage()}", [$ex]);
            $stats['options'] = ['generated' => false];
        }

        $stats['generated'] = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());
        $stats['generationTimeInMs'] = round((microtime(true) - $startGeneration) * 1000);

        //---------------------------------------------------
        // Save the results

        // Empty the collection
        $this->apiStatsLpasCollection->delete();

        // Add the new data
        $this->apiStatsLpasCollection->insert($stats);

        //---

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
            $month['started'] = $this->apiLpaCollection->countBetween($start, $end, 'startedAt');

            // Created if we have a createdAt, but no completedAt...
            $month['created'] = $this->apiLpaCollection->countBetween($start, $end, 'createdAt');

            // Count all the LPAs that have a completedAt...
            $month['completed'] = $this->apiLpaCollection->countBetween($start, $end, 'completedAt');

            $byMonth[date('Y-m', $start->getTimestamp())] = $month;

            // Modify dates, going back on month...
            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        $summary = [];

        // Broken down by type
        $pf = [];

        // Started if we have a startedAt, but no createdAt...
        $summary['started'] = $pf['started'] = $this->apiLpaCollection->countStartedForType(Document::LPA_TYPE_PF);

        // Created if we have a createdAt, but no completedAt...
        $summary['created'] = $pf['created'] = $this->apiLpaCollection->countCreatedForType(Document::LPA_TYPE_PF);

        // Count all the LPAs that have a completedAt...
        $summary['completed'] = $pf['completed'] = $this->apiLpaCollection->countCompletedForType(Document::LPA_TYPE_PF);

        $hw = [];

        // Started if we have a startedAt, but no createdAt...
        $summary['started'] += $hw['started'] = $this->apiLpaCollection->countStartedForType(Document::LPA_TYPE_HW);

        // Created if we have a createdAt, but no completedAt...
        $summary['created'] += $hw['created'] = $this->apiLpaCollection->countCreatedForType(Document::LPA_TYPE_HW);

        // Count all the LPAs that have a completedAt...
        $summary['completed'] += $hw['completed'] = $this->apiLpaCollection->countCompletedForType(Document::LPA_TYPE_HW);

        // Deleted LPAs have no 'document'...
        $summary['deleted'] = $this->apiLpaCollection->countDeleted();

        ksort($byMonth);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000),
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
            
            $results['by-month'][date('Y-m', $ts)] = $this->apiWhoCollection->getStatsForTimeRange(
                new DateTime("@{$ts}"),
                new DateTime("@{$lastTimestamp}"),
                WhoAreYou::options()
            );

            $lastTimestamp = $ts;
        }

        $results['all'] = $this->apiWhoCollection->getStatsForTimeRange(new DateTime("@0"), new DateTime(), WhoAreYou::options());

        ksort($results['by-month']);

        $results['generated'] = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());
        $results['generationTimeInMs'] = round((microtime(true) - $startGeneration) * 1000);

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

            $month['completed'] = $this->apiLpaCollection->countCompletedBetween($start, $end);

            $month['contactByEmail'] = $this->apiLpaCollection->countCompletedBetweenCorrespondentEmail($start, $end);

            $month['contactByPhone'] = $this->apiLpaCollection->countCompletedBetweenCorrespondentPhone($start, $end);

            $month['contactByPost'] = $this->apiLpaCollection->countCompletedBetweenCorrespondentPost($start, $end);

            $month['contactInEnglish'] = $this->apiLpaCollection->countCompletedBetweenCorrespondentEnglish($start, $end);

            $month['contactInWelsh'] = $this->apiLpaCollection->countCompletedBetweenCorrespondentWelsh($start, $end);

            $correspondenceStats[date('Y-m', $start->getTimestamp())] = $month;

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        ksort($correspondenceStats);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000),
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

            $month['completed'] = $this->apiLpaCollection->countCompletedBetween($start, $end);

            $month['preferencesStated'] = $this->apiLpaCollection->countCompletedBetweenWithPreferences($start, $end);

            $month['instructionsStated'] = $this->apiLpaCollection->countCompletedBetweenWithInstructions($start, $end);

            $preferencesInstructionsStats[date('Y-m', $start->getTimestamp())] = $month;

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        ksort($preferencesInstructionsStats);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000),
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

            $month['completed'] = $this->apiLpaCollection->countCompletedBetween($start, $end);

            $month['type'] = [
                Document::LPA_TYPE_HW => $this->apiLpaCollection->countCompletedBetweenByType($start, $end, Document::LPA_TYPE_HW),
                Document::LPA_TYPE_PF => $this->apiLpaCollection->countCompletedBetweenByType($start, $end, Document::LPA_TYPE_PF),
            ];

            $month['canSign'] = [
                'true'  => $this->apiLpaCollection->countCompletedBetweenByCanSign($start, $end, true),
                'false' => $this->apiLpaCollection->countCompletedBetweenByCanSign($start, $end, false),
            ];

            $month['replacementAttorneys'] = [
                'yes'       => $this->apiLpaCollection->countCompletedBetweenHasActors($start, $end, 'replacementAttorneys'),
                'no'        => $this->apiLpaCollection->countCompletedBetweenHasNoActors($start, $end, 'replacementAttorneys'),
                'multiple'  => $this->apiLpaCollection->countCompletedBetweenHasMultipleActors($start, $end, 'replacementAttorneys'),
            ];

            $month['peopleToNotify'] = [
                'yes'       => $this->apiLpaCollection->countCompletedBetweenHasActors($start, $end, 'peopleToNotify'),
                'no'        => $this->apiLpaCollection->countCompletedBetweenHasNoActors($start, $end, 'peopleToNotify'),
                'multiple'  => $this->apiLpaCollection->countCompletedBetweenHasMultipleActors($start, $end, 'peopleToNotify'),
            ];

            $month['whoIsRegistering'] = [
                'donor'     => $this->apiLpaCollection->countCompletedBetweenDonorRegistering($start, $end),
                'attorneys' => $this->apiLpaCollection->countCompletedBetweenAttorneyRegistering($start, $end),
            ];

            $month['repeatCaseNumber'] = [
                'yes' => $this->apiLpaCollection->countCompletedBetweenCaseNumber($start, $end, true),
                'no'  => $this->apiLpaCollection->countCompletedBetweenCaseNumber($start, $end, false),
            ];

            $month['payment'] = [
                'reducedFeeReceivesBenefits' => $this->apiLpaCollection->countCompletedBetweenFeeType($start, $end, true, true, null, null),
                'reducedFeeUniversalCredit'  => $this->apiLpaCollection->countCompletedBetweenFeeType($start, $end, false, null, false, true),
                'reducedFeeLowIncome'        => $this->apiLpaCollection->countCompletedBetweenFeeType($start, $end, false, null, true, false),
                'notApply'                   => $this->apiLpaCollection->countCompletedBetweenFeeType($start, $end, null, null, null, null),
                Payment::PAYMENT_TYPE_CARD   => $this->apiLpaCollection->countCompletedBetweenPaymentType($start, $end, Payment::PAYMENT_TYPE_CARD),
                Payment::PAYMENT_TYPE_CHEQUE => $this->apiLpaCollection->countCompletedBetweenPaymentType($start, $end, Payment::PAYMENT_TYPE_CHEQUE),
            ];

            $month['primaryAttorneys'] = [
                'multiple'  => $this->apiLpaCollection->countCompletedBetweenHasMultipleActors($start, $end, 'primaryAttorneys'),
            ];

            $month['primaryAttorneyDecisions'] = [
                'when' => [
                    PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW         => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'primaryAttorneyDecisions', 'when', PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW),
                    PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'primaryAttorneyDecisions', 'when', PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY),
                ],
                'how' => [
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'primaryAttorneyDecisions', 'how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY),
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY               => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'primaryAttorneyDecisions', 'how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY),
                    AbstractDecisions::LPA_DECISION_HOW_DEPENDS               => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'primaryAttorneyDecisions', 'how', AbstractDecisions::LPA_DECISION_HOW_DEPENDS),
                ],
                'canSustainLife' => [
                    'true'  => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'primaryAttorneyDecisions', 'canSustainLife', true),
                    'false' => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'primaryAttorneyDecisions', 'canSustainLife', false),
                ]
            ];

            $month['replacementAttorneyDecisions'] = [
                'when' => [
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST   => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'replacementAttorneyDecisions', 'when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST),
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST    => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'replacementAttorneyDecisions', 'when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST),
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'replacementAttorneyDecisions', 'when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS),
                ],
                'how' => [
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'replacementAttorneyDecisions', 'how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY),
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY               => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'replacementAttorneyDecisions', 'how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY),
                    AbstractDecisions::LPA_DECISION_HOW_DEPENDS               => $this->apiLpaCollection->countCompletedBetweenWithAttorneyDecisions($start, $end, 'replacementAttorneyDecisions', 'how', AbstractDecisions::LPA_DECISION_HOW_DEPENDS),
                ]
            ];

            $month['trust'] = [
                'primaryAttorneys'     => $this->apiLpaCollection->countCompletedBetweenWithTrust($start, $end, 'primaryAttorneys'),
                'replacementAttorneys' => $this->apiLpaCollection->countCompletedBetweenWithTrust($start, $end, 'replacementAttorneys'),
            ];

            $month['certificateProviderSkipped'] = [
                'yes' => $this->apiLpaCollection->countCompletedBetweenCertificateProviderSkipped($start, $end, true),
                'no'  => $this->apiLpaCollection->countCompletedBetweenCertificateProviderSkipped($start, $end, false),
            ];

            $optionStats[date('Y-m', $start->getTimestamp())] = $month;

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        ksort($optionStats);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000),
            'by-month' => $optionStats
        ];
    }
}
