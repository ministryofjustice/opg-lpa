<?php
namespace Application\Model\Service\System;

use Application\DataAccess\Mongo\CollectionFactory;
use Application\Traits\LogTrait;
use DateTime;
use Exception;
use MongoDB\BSON\Javascript as MongoCode;
use MongoDB\BSON\ObjectID as MongoId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime as MongoDate;
use MongoDB\Collection;
use MongoDB\Driver\Command;
use MongoDB\Driver\ReadPreference;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Generate LPA stats and saves the results back into MongoDB.
 * To run, bash into apiv2, cd to app and run 'php public/index.php generate-stats'
 *
 * Class Stats
 * @package Application\Model\Service\System
 */
class Stats implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    use LogTrait;

    public function generate()
    {
        $stats = [];

        $startGeneration = microtime(true);

        try {
            $stats['lpas'] = $this->getLpaStats();
            $this->info("Successfully generated lpas stats");
        } catch (Exception $ex) {
            $this->err("Failed to generate lpas stats due to {$ex->getMessage()}", [$ex]);
            $stats['lpas'] = ['generated' => false];
        }

        try {
            $stats['lpasPerUser'] = $this->getLpasPerUser();
            $this->info("Successfully generated lpasPerUser stats");
        } catch (Exception $ex) {
            $this->err("Failed to generate lpasPerUser stats due to {$ex->getMessage()}", [$ex]);
            $stats['lpasPerUser'] = ['generated' => false];
        }

        try {
            $stats['who'] = $this->getWhoAreYou();
            $this->info("Successfully generated who stats");
        } catch (Exception $ex) {
            $this->err("Failed to generate who stats due to {$ex->getMessage()}", [$ex]);
            $stats['who'] = ['generated' => false];
        }

        try {
            $stats['correspondence'] = $this->getCorrespondenceStats();
            $this->info("Successfully generated correspondence stats");
        } catch (Exception $ex) {
            $this->err("Failed to generate correspondence stats due to {$ex->getMessage()}", [$ex]);
            $stats['correspondence'] = ['generated' => false];
        }

        try {
            $stats['preferencesInstructions'] = $this->getPreferencesInstructionsStats();
            $this->info("Successfully generated preferencesInstructions stats");
        } catch (Exception $ex) {
            $this->err("Failed to generate preferencesInstructions stats due to {$ex->getMessage()}", [$ex]);
            $stats['preferencesInstructions'] = ['generated' => false];
        }

        try {
            $stats['radioButtons'] = $this->getRadioButtonsStats();
            $this->info("Successfully generated radioButtons stats");
        } catch (Exception $ex) {
            $this->err("Failed to generate radioButtons stats due to {$ex->getMessage()}", [$ex]);
            $stats['radioButtons'] = ['generated' => false];
        }

        $stats['generated'] = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());
        $stats['generationTimeInMs'] = round((microtime(true) - $startGeneration) * 1000);

        //---------------------------------------------------
        // Save the results

        $collection = $this->getServiceLocator()->get(CollectionFactory::class . '-stats-lpas');

        // Empty the collection
        $collection->deleteMany([]);

        // Add the new data
        $collection->insertOne($stats);

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

        $collection = $this->getCollection('lpa');

        // Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        // Broken down by month
        $byMonth = [];

        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        // Go back 4 months...
        for ($i = 1; $i <= 4; $i++) {
            $month = [];

            // Create MongoDate date range
            $dateRange = [
                '$gte' => new MongoDate($start),
                '$lte' => new MongoDate($end)
            ];

            // Started if we have a startedAt, but no createdAt...
            $month['started'] = $collection->count([
                'startedAt' => $dateRange
            ], $readPreference);

            // Created if we have a createdAt, but no completedAt...
            $month['created'] = $collection->count([
                'createdAt' => $dateRange
            ], $readPreference);

            // Count all the LPAs that have a completedAt...
            $month['completed'] = $collection->count([
                'completedAt' => $dateRange
            ], $readPreference);

            $byMonth[date('Y-m', $start->getTimestamp())] = $month;

            // Modify dates, going back on month...
            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        $summary = [];

        // Broken down by type
        $pf = [];

        // Started if we have a startedAt, but no createdAt...
        $summary['started'] = $pf['started'] = $collection->count([
            'startedAt' => [
                '$ne' => null
            ],
            'createdAt' => null,
            'document.type' => Document::LPA_TYPE_PF
        ], $readPreference);

        // Created if we have a createdAt, but no completedAt...
        $summary['created'] = $pf['created'] = $collection->count([
            'createdAt' => [
                '$ne' => null
            ],
            'completedAt' => null,
            'document.type' => Document::LPA_TYPE_PF
        ], $readPreference);

        // Count all the LPAs that have a completedAt...
        $summary['completed'] = $pf['completed'] = $collection->count([
            'completedAt' => [
                '$ne' => null
            ],
            'document.type' => Document::LPA_TYPE_PF
        ], $readPreference);

        $hw = [];

        // Started if we have a startedAt, but no createdAt...
        $summary['started'] += $hw['started'] = $collection->count([
            'startedAt' => [
                '$ne' => null
            ],
            'createdAt' => null,
            'document.type' => Document::LPA_TYPE_HW
        ], $readPreference);

        // Created if we have a createdAt, but no completedAt...
        $summary['created'] += $hw['created'] = $collection->count([
            'createdAt' => [
                '$ne' => null
            ],
            'completedAt' => null,
            'document.type' => Document::LPA_TYPE_HW
        ], $readPreference);

        // Count all the LPAs that have a completedAt...
        $summary['completed'] += $hw['completed'] = $collection->count([
            'completedAt' => [
                '$ne' => null
            ],
            'document.type' => Document::LPA_TYPE_HW
        ], $readPreference);

        // Deleted LPAs have no 'document'...
        $summary['deleted'] = $collection->count([
            'document' => [
                '$exists' => false
            ]
        ], $readPreference);

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
     * Returns a list of lpa counts and user counts, in order to
     * answer questions of the form how many users have five LPAs?
     *
     * @return array
     *
     * The key of the return array is the number of LPAs
     * The value is the number of users with this many LPAs
     */
    private function getLpasPerUser()
    {
        $startGeneration = microtime(true);

        $collection = $this->getCollection('lpa');

        //------------------------------------

        // Returns the number of LPAs under each userId

        $map = new MongoCode(
            'function() {
                if( this.user ){
                    emit(this.user,1);
                }
            }'
        );

        $reduce = new MongoCode(
            'function(user, lpas) {
                return lpas.length;
            }'
        );

        $manager = $collection->getManager();

        $command = new Command([
            'mapreduce' => $collection->getCollectionName(),
            'map' => $map,
            'reduce' => $reduce,
            'out' => ['inline'=>1],
            'query' => [ 'user' => [ '$exists'=>true ] ],
        ]);

        // Stats can (ideally) be processed on a secondary.
        $document = $cursor = $manager->executeCommand(
            $collection->getDatabaseName(),
            $command,
            new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        )->toArray()[0];

        //------------------------------------

        /*
         * This creates an array where:
         *  key = a number or LPAs
         *  value = the number of users with that number of LPAs.
         *
         * This lets us say:
         *  N users have X LPAs
         */

        $lpasPerUser = array_reduce(
            $document->results,
            function ($carry, $item) {

                $count = (int)$item->value;

                if (!isset($carry[$count])) {
                    $carry[$count] = 1;
                } else {
                    $carry[$count]++;
                }

                return $carry;
            },
            array()
        );

        //---

        // Sort by key so they're pre-ordered when sent to Mongo.
        krsort($lpasPerUser);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000),
            'all' => $lpasPerUser
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

            $results['by-month'][date('Y-m', $ts)] = $this->getWhoAreYouStatsForTimeRange($ts, $lastTimestamp);

            $lastTimestamp = $ts;
        }

        $results['all'] = $this->getWhoAreYouStatsForTimeRange(0, time());

        ksort($results['by-month']);

        $results['generated'] = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());
        $results['generationTimeInMs'] = round((microtime(true) - $startGeneration) * 1000);

        return $results;
    }

    /**
     * Return the WhoAreYou values for a specific date range.
     *
     * @param $start
     * @param $end
     * @return array
     */
    private function getWhoAreYouStatsForTimeRange($start, $end)
    {
        $collection = $this->getCollection('stats-who');

        // Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        // Convert the timestamps to MongoIds
        $start = str_pad(dechex($start), 8, "0", STR_PAD_LEFT);
        $start = new MongoId($start."0000000000000000");

        $end = str_pad(dechex($end), 8, "0", STR_PAD_LEFT);
        $end = new MongoId($end."0000000000000000");

        $range = [
            '$gte' => $start,
            '$lte' => $end
        ];

        $result = [];

        // Base the groupings on the Model's data.
        $options = WhoAreYou::options();

        // For each top level 'who' level...
        foreach ($options as $topLevel => $details) {
            // Get the count for all top level...
            $result[$topLevel] = [
                'count' => $collection->count([
                    'who' => $topLevel,
                    '_id' => $range
                ], $readPreference),
            ];

            // Count all the subquestion values
            $result[$topLevel]['subquestions'] = [];

            foreach ($details['subquestion'] as $subquestion) {
                if (empty($subquestion)) {
                    continue;
                }

                $result[$topLevel]['subquestions'][$subquestion] = $collection->count([
                    'who' => $topLevel,
                    'subquestion' => $subquestion,
                    '_id' => $range
                ], $readPreference);
            }
        }

        return $result;
    }

    private function getCorrespondenceStats()
    {
        $startGeneration = microtime(true);

        $collection = $this->getCollection('lpa');

        // Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        $correspondenceStats = [];

        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        // Go back 4 months...
        for ($i = 1; $i <= 4; $i++) {
            $month = [];

            // Create MongoDate date range
            $dateRange = [
                '$gte' => new MongoDate($start),
                '$lte' => new MongoDate($end)
            ];

            $month['completed'] = $collection->count([
                'completedAt' => $dateRange
            ], $readPreference);

            $month['contactByEmail'] = $collection->count([
                'completedAt' => $dateRange,
                'document.correspondent' => [
                    '$ne' => null
                ], 'document.correspondent.email' => [
                    '$ne' => null
                ]
            ], $readPreference);

            $month['contactByPhone'] = $collection->count([
                'completedAt' => $dateRange,
                'document.correspondent' => [
                    '$ne' => null
                ], 'document.correspondent.phone' => [
                    '$ne' => null
                ]
            ], $readPreference);

            $month['contactByPost'] = $collection->count([
                'completedAt' => $dateRange,
                'document.correspondent' => [
                    '$ne' => null
                ], 'document.correspondent.contactByPost' => true
            ], $readPreference);

            $month['contactInEnglish'] = $collection->count([
                'completedAt' => $dateRange,
                'document.correspondent' => [
                    '$ne' => null
                ], 'document.correspondent.contactInWelsh' => false
            ], $readPreference);

            $month['contactInWelsh'] = $collection->count([
                'completedAt' => $dateRange,
                'document.correspondent' => [
                    '$ne' => null
                ], 'document.correspondent.contactInWelsh' => true
            ], $readPreference);

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

    private function getPreferencesInstructionsStats()
    {
        $startGeneration = microtime(true);

        $collection = $this->getCollection('lpa');

        // Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        $preferencesInstructionsStats = [];

        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        // Go back 4 months...
        for ($i = 1; $i <= 4; $i++) {
            $month = [];

            // Create MongoDate date range
            $dateRange = [
                '$gte' => new MongoDate($start),
                '$lte' => new MongoDate($end)
            ];

            $month['completed'] = $collection->count([
                'completedAt' => $dateRange
            ], $readPreference);

            $month['preferencesStated'] = $collection->count([
                'completedAt' => $dateRange,
                'document.preference' => new Regex('.+', '')
            ], $readPreference);

            $month['instructionsStated'] = $collection->count([
                'completedAt' => $dateRange,
                'document.instruction' => new Regex('.+', '')
            ], $readPreference);

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

    private function getRadioButtonsStats()
    {
        $startGeneration = microtime(true);

        $collection = $this->getCollection('lpa');

        // Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        $radioButtonStats = [];

        $start = new DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        // Go back 4 months...
        for ($i = 1; $i <= 4; $i++) {
            $month = [];

            // Create MongoDate date range
            $dateRange = [
                '$gte' => new MongoDate($start),
                '$lte' => new MongoDate($end)
            ];

            $month['completed'] = $collection->count([
                'completedAt' => $dateRange
            ], $readPreference);

            //https://opgtransform.atlassian.net/browse/LPA-2492
            //db.getCollection('lpa').count({"document" : {$ne : null}, "document.type" : "health-and-welfare"})
            //db.getCollection('lpa').count({"document" : {$ne : null}, "document.type" : "property-and-financial"})

            $month['type'] = [
                Document::LPA_TYPE_HW => $collection->count([
                    'completedAt' => $dateRange,
                    'document.type' => Document::LPA_TYPE_HW
                ], $readPreference),
                Document::LPA_TYPE_PF => $collection->count([
                    'completedAt' => $dateRange,
                    'document.type' => Document::LPA_TYPE_PF
                ], $readPreference)
            ];

            //https://opgtransform.atlassian.net/browse/LPA-2493
            //db.getCollection('lpa').count({"document.donor" : {$ne : null}, "document.donor.canSign" : true})
            //db.getCollection('lpa').count({"document.donor" : {$ne : null}, "document.donor.canSign" : false})

            $month['canSign'] = [
                'true' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.donor.canSign' => true
                ], $readPreference),
                'false' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.donor.canSign' => false
                ], $readPreference)
            ];

            //https://opgtransform.atlassian.net/browse/LPA-2495
            //db.getCollection('lpa').count({"document.replacementAttorneys" : {$ne : null}, "document.replacementAttorneys" : { $gt: [] }})
            //db.getCollection('lpa').count({"document.replacementAttorneys" : null})

            $month['replacementAttorneys'] = [
                'yes' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.replacementAttorneys' => [
                        '$gt' => []
                    ]
                ], $readPreference),
                'no' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.replacementAttorneys' => null
                ], $readPreference),
                'multiple' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.replacementAttorneys' => [
                        '$ne' => null
                    ],
                    '$where' => 'this.document.replacementAttorneys.length > 1'
                ], $readPreference)
            ];

            //https://opgtransform.atlassian.net/browse/LPA-2496
            //db.getCollection('lpa').count({"document.peopleToNotify" : {$ne : null}, "document.peopleToNotify" : { $gt: [] }})
            //db.getCollection('lpa').count({"document.peopleToNotify" : null})

            $month['peopleToNotify'] = [
                'yes' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.peopleToNotify' => [
                        '$gt' => []
                    ]
                ], $readPreference),
                'no' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.peopleToNotify' => null
                ], $readPreference),
                'multiple' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.peopleToNotify' => [
                        '$ne' => null
                    ],
                    '$where' => 'this.document.peopleToNotify.length > 1'
                ], $readPreference)
            ];

            //https://opgtransform.atlassian.net/browse/LPA-2497
            //db.getCollection('lpa').count({"document.whoIsRegistering" : {$ne : null}, "document.whoIsRegistering" : "donor"})
            //db.getCollection('lpa').count({"document.whoIsRegistering" : {$ne : null}, "document.whoIsRegistering" : { $gt: [] }}) //Attorney(s)

            $month['whoIsRegistering'] = [
                'donor' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.whoIsRegistering' => null
                ], $readPreference),
                'attorneys' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.whoIsRegistering' => [
                        '$gt' => []
                    ]
                ], $readPreference)
            ];

            //https://opgtransform.atlassian.net/browse/LPA-2498
            //db.getCollection('lpa').count({"repeatCaseNumber" : { $ne: null }})

            $month['repeatCaseNumber'] = [
                'yes' => $collection->count([
                    'completedAt' => $dateRange,
                    'repeatCaseNumber' => [
                        '$ne' => null
                    ]
                ], $readPreference),
                'no' => $collection->count([
                    'completedAt' => $dateRange,
                    'repeatCaseNumber' => null
                ], $readPreference)
            ];

            //https://opgtransform.atlassian.net/browse/LPA-2499
            //db.getCollection('lpa').count({"payment" : {$ne : null}, "payment.reducedFeeReceivesBenefits" : true, "payment.reducedFeeAwardedDamages" : true, "payment.reducedFeeLowIncome" : null, "payment.reducedFeeUniversalCredit" : null}) //reducedFeeReceivesBenefits
            //db.getCollection('lpa').count({"payment" : {$ne : null}, "payment.reducedFeeReceivesBenefits" : false, "payment.reducedFeeAwardedDamages" : null, "payment.reducedFeeLowIncome" : false, "payment.reducedFeeUniversalCredit" : true}) //reducedFeeUniversalCredit
            //db.getCollection('lpa').count({"payment" : {$ne : null}, "payment.reducedFeeReceivesBenefits" : false, "payment.reducedFeeAwardedDamages" : null, "payment.reducedFeeLowIncome" : true, "payment.reducedFeeUniversalCredit" : false}) //reducedFeeLowIncome
            //db.getCollection('lpa').count({"payment" : {$ne : null}, "payment.reducedFeeReceivesBenefits" : null, "payment.reducedFeeAwardedDamages" : null, "payment.reducedFeeLowIncome" : null, "payment.reducedFeeUniversalCredit" : null}) //notApply

            //https://opgtransform.atlassian.net/browse/LPA-2500
            //db.getCollection('lpa').count({"payment" : {$ne : null}, "payment.method" : "card"})
            //db.getCollection('lpa').count({"payment" : {$ne : null}, "payment.method" : "cheque"})

            $month['payment'] = [
                'reducedFeeReceivesBenefits' => $collection->count([
                    'completedAt' => $dateRange,
                    'payment.reducedFeeReceivesBenefits' => true,
                    'payment.reducedFeeAwardedDamages' => true,
                    'payment.reducedFeeLowIncome' => null,
                    'payment.reducedFeeUniversalCredit' => null,
                ], $readPreference),
                'reducedFeeUniversalCredit' => $collection->count([
                    'completedAt' => $dateRange,
                    'payment.reducedFeeReceivesBenefits' => false,
                    'payment.reducedFeeAwardedDamages' => null,
                    'payment.reducedFeeLowIncome' => false,
                    'payment.reducedFeeUniversalCredit' => true,
                ], $readPreference),
                'reducedFeeLowIncome' => $collection->count([
                    'completedAt' => $dateRange,
                    'payment.reducedFeeReceivesBenefits' => false,
                    'payment.reducedFeeAwardedDamages' => null,
                    'payment.reducedFeeLowIncome' => true,
                    'payment.reducedFeeUniversalCredit' => false,
                ], $readPreference),
                'notApply' => $collection->count([
                    'completedAt' => $dateRange,
                    'payment.reducedFeeReceivesBenefits' => null,
                    'payment.reducedFeeAwardedDamages' => null,
                    'payment.reducedFeeLowIncome' => null,
                    'payment.reducedFeeUniversalCredit' => null,
                ], $readPreference),
                Payment::PAYMENT_TYPE_CARD => $collection->count([
                    'completedAt' => $dateRange,
                    'payment.method' => Payment::PAYMENT_TYPE_CARD,
                ], $readPreference),
                Payment::PAYMENT_TYPE_CHEQUE => $collection->count([
                    'completedAt' => $dateRange,
                    'payment.method' => Payment::PAYMENT_TYPE_CHEQUE,
                ], $readPreference)
            ];

            //https://opgtransform.atlassian.net/browse/LPA-2494
            //db.getCollection('lpa').count({"document.primaryAttorneyDecisions" : {$ne : null}, "document.primaryAttorneyDecisions.when" : "now"})
            //db.getCollection('lpa').count({"document.primaryAttorneyDecisions" : {$ne : null}, "document.primaryAttorneyDecisions.when" : "no-capacity"})

            //https://opgtransform.atlassian.net/browse/LPA-2501
            //db.getCollection('lpa').count({"document.primaryAttorneyDecisions" : {$ne : null}, "document.primaryAttorneyDecisions.how" : "jointly-attorney-severally"})
            //db.getCollection('lpa').count({"document.primaryAttorneyDecisions" : {$ne : null}, "document.primaryAttorneyDecisions.how" : "jointly"})
            //db.getCollection('lpa').count({"document.primaryAttorneyDecisions" : {$ne : null}, "document.primaryAttorneyDecisions.how" : "depends"})
            //db.getCollection('lpa').count({"document.primaryAttorneyDecisions" : {$ne : null}, "document.primaryAttorneyDecisions.how" : null}) //Single attorney

            //https://opgtransform.atlassian.net/browse/LPA-2504
            //db.getCollection('lpa').count({"document" : {$ne : null}, "document.type" : "health-and-welfare", "document.primaryAttorneyDecisions" : {$ne : null}, "document.primaryAttorneyDecisions.canSustainLife" : true})
            //db.getCollection('lpa').count({"document" : {$ne : null}, "document.type" : "health-and-welfare", "document.primaryAttorneyDecisions" : {$ne : null}, "document.primaryAttorneyDecisions.canSustainLife" : false})

            $month['primaryAttorneys'] = [
                'multiple' => $collection->count([
                    'completedAt' => $dateRange,
                    'document.primaryAttorneys' => [
                        '$ne' => null
                    ],
                    '$where' => 'this.document.primaryAttorneys.length > 1'
                ], $readPreference)
            ];

            $month['primaryAttorneyDecisions'] = [
                'when' => [
                    PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW => $collection->count([
                        'completedAt' => $dateRange,
                        'document.primaryAttorneyDecisions.when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW
                    ], $readPreference),
                    PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY => $collection->count([
                        'completedAt' => $dateRange,
                        'document.primaryAttorneyDecisions.when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY
                    ], $readPreference)
                ],
                'how' => [
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY => $collection->count([
                        'completedAt' => $dateRange,
                        'document.primaryAttorneyDecisions.how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                    ], $readPreference),
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY => $collection->count([
                        'completedAt' => $dateRange,
                        'document.primaryAttorneyDecisions.how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY
                    ], $readPreference),
                    AbstractDecisions::LPA_DECISION_HOW_DEPENDS => $collection->count([
                        'completedAt' => $dateRange,
                        'document.primaryAttorneyDecisions.how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                    ], $readPreference)
                ],
                'canSustainLife' => [
                    'true' => $collection->count([
                        'completedAt' => $dateRange,
                        'document.primaryAttorneyDecisions.canSustainLife' => true
                    ], $readPreference),
                    'false' => $collection->count([
                        'completedAt' => $dateRange,
                        'document.primaryAttorneyDecisions.canSustainLife' => false
                    ], $readPreference)
                ]
            ];

            //https://opgtransform.atlassian.net/browse/LPA-2502
            //db.getCollection('lpa').count({"document.replacementAttorneyDecisions" : {$ne : null}, "document.replacementAttorneyDecisions.how" : "jointly-attorney-severally"})
            //db.getCollection('lpa').count({"document.replacementAttorneyDecisions" : {$ne : null}, "document.replacementAttorneyDecisions.how" : "jointly"})
            //db.getCollection('lpa').count({"document.replacementAttorneyDecisions" : {$ne : null}, "document.replacementAttorneyDecisions.how" : "depends"})
            //db.getCollection('lpa').count({"document.replacementAttorneyDecisions" : {$ne : null}, "document.replacementAttorneyDecisions.how" : null}) //Single attorney

            //https://opgtransform.atlassian.net/browse/LPA-2503
            //db.getCollection('lpa').count({"document.replacementAttorneyDecisions" : {$ne : null}, "document.replacementAttorneyDecisions.when" : "first"})
            //db.getCollection('lpa').count({"document.replacementAttorneyDecisions" : {$ne : null}, "document.replacementAttorneyDecisions.when" : "last"})
            //db.getCollection('lpa').count({"document.replacementAttorneyDecisions" : {$ne : null}, "document.replacementAttorneyDecisions.when" : "depends"})

            $month['replacementAttorneyDecisions'] = [
                'when' => [
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST => $collection->count([
                        'completedAt' => $dateRange,
                        'document.replacementAttorneyDecisions.when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST
                    ], $readPreference),
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST => $collection->count([
                        'completedAt' => $dateRange,
                        'document.replacementAttorneyDecisions.when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST
                    ], $readPreference),
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS => $collection->count([
                        'completedAt' => $dateRange,
                        'document.replacementAttorneyDecisions.when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS
                    ], $readPreference)
                ],
                'how' => [
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY => $collection->count([
                        'completedAt' => $dateRange,
                        'document.replacementAttorneyDecisions.how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                    ], $readPreference),
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY => $collection->count([
                        'completedAt' => $dateRange,
                        'document.replacementAttorneyDecisions.how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY
                    ], $readPreference),
                    AbstractDecisions::LPA_DECISION_HOW_DEPENDS => $collection->count([
                        'completedAt' => $dateRange,
                        'document.replacementAttorneyDecisions.how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                    ], $readPreference)
                ]
            ];

            $radioButtonStats[date('Y-m', $start->getTimestamp())] = $month;

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        ksort($radioButtonStats);

        return [
            'generated' => date('d/m/Y H:i:s', (new DateTime())->getTimestamp()),
            'generationTimeInMs' => round((microtime(true) - $startGeneration) * 1000),
            'by-month' => $radioButtonStats
        ];
    }

    /**
     * @param $collection string Name of the requested collection.
     * @return Collection
     */
    private function getCollection($collection)
    {
        /** @var Collection $collection */
        $collection = $this->getServiceLocator()->get(CollectionFactory::class . "-{$collection}");
        return $collection;
    }
} // class
