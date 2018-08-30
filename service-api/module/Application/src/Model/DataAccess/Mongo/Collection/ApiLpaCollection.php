<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use Traversable;
use DateTime;
use Application\Library\DateTime as MillisecondDateTime;
use Application\Model\DataAccess\Repository\Application\LockedException;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Mongo\DateCallback;
use MongoDB\BSON\Javascript as MongoCode;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\Command;
use MongoDB\Driver\ReadPreference;
use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;

class ApiLpaCollection extends AbstractCollection implements ApplicationRepositoryInterface
{
    /**
     * @var MongoCollection
     */
    protected $collection;

    /**
     * @param MongoCollection $collection
     */
    public function __construct(MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get an LPA by ID, and user ID if provided
     *
     * @param int $id
     * @param string $userId
     * @return array|null
     */
    public function getById(int $id, ?string $userId = null) : ?array
    {
        $criteria = [
            '_id' => $id
        ];

        if (!is_null($userId)) {
            $criteria['user'] = $userId;
        }

        $result = $this->collection->findOne($criteria);

        if (is_array($result) && isset($result['_id'])) {
            $result = ['id' => $result['_id']] + $result;
        }

        return $result;
    }

    /**
     * Counts the number of results for the given criteria.
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria) : int
    {
        return $this->collection->count($criteria);
    }

    /**
     * @param array $criteria
     * @param array $options
     * @return Traversable
     */
    public function fetch(array $criteria, array $options = []) : Traversable
    {
        $result = $this->collection->find($criteria, $options);

        foreach ($result as $data) {
            if (isset($data['_id'])) {
                $data['id'] = $data['_id'];
            }

            yield $data;
        }
    }

    /**
     * @param string $userId
     * @param array $options
     * @return Traversable
     */
    public function fetchByUserId(string $userId, array $options = []) : Traversable
    {
        return $this->fetch([
            'user' => $userId,
        ], $options);
    }

    /**
     * @param Lpa $lpa
     * @return bool
     */
    public function insert(Lpa $lpa) : bool
    {
        $result = $this->collection->insertOne($this->prepare($lpa->toArray(true)));

        return ($result->getInsertedCount() == 1);
    }

    /**
     * Update the LPA
     *
     * @param Lpa $lpa
     * @return bool
     */
    public function update(Lpa $lpa) : bool
    {
        // Check to ensure the LPA isn't locked.
        $inDbLpa = $this->getById($lpa->getId());

        $updateTimestamp = true;

        if (!is_null($inDbLpa)) {
            $inDbLpa = new Lpa($inDbLpa);

            if ($inDbLpa->isLocked()) {
                throw new LockedException('LPA has already been locked.');
            }

            $updateTimestamp = !$lpa->equalsIgnoreMetadata($inDbLpa);
        }

        //------------------------------------------

        //  If instrument created, record the date.
        if ($lpa->isStateCreated()) {
            if (!($lpa->createdAt instanceof DateTime)) {
                $lpa->createdAt = new MillisecondDateTime();
            }
        } else {
            $lpa->createdAt = null;
        }

        // If completed, record the date.
        if ($lpa->isStateCompleted()) {
            // If we don't already have a complete date and the LPA is locked...
            if (!($lpa->completedAt instanceof DateTime) && $lpa->locked === true) {
                $lpa->completedAt = new MillisecondDateTime();
            }
        } else {
            $lpa->completedAt = null;
        }

        // If there's a donor, populate the free text search field
        $searchField = null;

        if ($lpa->document->donor != null) {
            $searchField = (string) $lpa->document->donor->name;
        }

        $lastUpdated = new UTCDateTime($lpa->updatedAt);

        if ($updateTimestamp === true) {
            // Record the time we updated the document.
            $lpa->updatedAt = new MillisecondDateTime();
        }

        // updatedAt is included in the query so that data isn't overwritten
        // if the Document has changed since this process loaded it.
        $result = $this->collection->updateOne(
            ['_id' => $lpa->id, 'updatedAt' => $lastUpdated ],
            ['$set' => array_merge($this->prepare($lpa->toArray(true)), ['search' => $searchField])],
            ['upsert' => false, 'multiple' => false]
        );

        // Ensure that one (and only one) document was updated.
        // If not, something when wrong.
        if ($result->getModifiedCount() !== 0 && $result->getModifiedCount() !== 1) {
            throw new RuntimeException('Unable to update LPA. This might be because "updatedAt" has changed.');
        }

        return true;
    }

    /**
     * @param int $lpaId
     * @param string $userId
     * @return bool
     */
    public function deleteById(int $lpaId, string $userId) : bool
    {
        //  We don't want to remove the document entirely as we need to make sure the same ID isn't reassigned
        $result = $this->collection->replaceOne([
            '_id' => (int)$lpaId,
            'user' => $userId,
        ], [
            'updatedAt' => new UTCDateTime(),
        ]);

        return $result->isAcknowledged() && $result->getModifiedCount() === 1;
    }

    /**
     * Get the count of LPAs between two dates for the timestamp field name provided
     *
     * $timestampFieldName can be one of:
     *  startedAt
     *  createdAt
     *  updatedAt
     *  completedAt
     *  lockedAt
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $timestampFieldName
     * @return int
     */
    public function countBetween(Datetime $start, Datetime $end, string $timestampFieldName) : int
    {
        //  Call from stats so (ideally) process on a secondary
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        return $this->collection->count([
            $timestampFieldName => [
                '$gte' => new UTCDateTime($start),
                '$lte' => new UTCDateTime($end)
            ]
        ], $readPreference);
    }

    /**
     * Count the number of LPAs started but not created for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countStartedForType(string $lpaType) : int
    {
        //  Call from stats so (ideally) process on a secondary
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        return $this->collection->count([
            'startedAt' => [
                '$ne' => null
            ],
            'createdAt' => null,
            'document.type' => $lpaType
        ], $readPreference);
    }

    /**
     * Count the number of LPAs created but not completed for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countCreatedForType(string $lpaType) : int
    {
        //  Call from stats so (ideally) process on a secondary
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        return $this->collection->count([
            'createdAt' => [
                '$ne' => null
            ],
            'completedAt' => null,
            'document.type' => $lpaType
        ], $readPreference);
    }

    /**
     * Count the number of LPAs completed for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countCompletedForType(string $lpaType) : int
    {
        //  Call from stats so (ideally) process on a secondary
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        return $this->collection->count([
            'completedAt' => [
                '$ne' => null
            ],
            'document.type' => $lpaType
        ], $readPreference);
    }

    /**
     * Count the number of deleted LPAs
     *
     * @return int
     */
    public function countDeleted() : int
    {
        //  Call from stats so (ideally) process on a secondary
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        return $this->collection->count([
            'document' => [
                '$exists' => false
            ]
        ], $readPreference);
    }

    /**
     * Returns a list of lpa counts and user counts, in order to
     * answer questions of the form how many users have five LPAs?
     *
     * The key of the return array is the number of LPAs
     * The value is the number of users with this many LPAs
     *
     * @return array
     */
    public function getLpasPerUser() : array
    {
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

        $manager = $this->collection->getManager();

        $command = new Command([
            'mapreduce' => $this->collection->getCollectionName(),
            'map' => $map,
            'reduce' => $reduce,
            'out' => ['inline'=>1],
            'query' => [ 'user' => [ '$exists'=>true ] ],
        ]);

        // Stats can (ideally) be processed on a secondary.
        $document = $cursor = $manager->executeCommand(
            $this->collection->getDatabaseName(),
            $command,
            new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        )->toArray()[0];

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
            []
        );

        // Sort by key so they're pre-ordered when sent to Mongo.
        krsort($lpasPerUser);

        return $lpasPerUser;
    }

    /**
     * Get the number of completed LPAs - with additional criteria if provided
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param array $additionalCriteria
     * @return int
     */
    public function countCompletedBetween(Datetime $start, Datetime $end, array $additionalCriteria = []) : int
    {
        //  Call from stats so (ideally) process on a secondary
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        $criteria = [
            'completedAt' => [
                '$gte' => new UTCDateTime($start),
                '$lte' => new UTCDateTime($end)
            ]
        ];

        if (!empty($additionalCriteria)) {
            $criteria = array_merge($criteria, $additionalCriteria);
        }

        return $this->collection->count($criteria, $readPreference);
    }

    /**
     * Get the number of completed LPAs with a correspondent that has entered an email address
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentEmail(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.correspondent' => [
                '$ne' => null
            ], 'document.correspondent.email' => [
                '$ne' => null
            ]
        ]);
    }

    /**
     * Get the number of completed LPAs with a correspondent that has entered phone number
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentPhone(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.correspondent' => [
                '$ne' => null
            ], 'document.correspondent.phone' => [
                '$ne' => null
            ]
        ]);
    }

    /**
     * Get the number of completed LPAs with a correspondent that has entered a postal address
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentPost(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.correspondent' => [
                '$ne' => null
            ], 'document.correspondent.contactByPost' => true
        ]);
    }

    /**
     * Get the number of completed LPAs with a correspondent that has requested to be contacted in English
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentEnglish(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.correspondent' => [
                '$ne' => null
            ], 'document.correspondent.contactInWelsh' => false
        ]);
    }

    /**
     * Get the number of completed LPAs with a correspondent that has requested to be contacted in Welsh
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentWelsh(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.correspondent' => [
                '$ne' => null
            ], 'document.correspondent.contactInWelsh' => true
        ]);
    }

    /**
     * Get the number of completed LPAs with preferences
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenWithPreferences(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.preference' => new Regex('.+', '')
        ]);
    }

    /**
     * Get the number of completed LPAs with instructions
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenWithInstructions(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.instruction' => new Regex('.+', '')
        ]);
    }

    /**
     * Get the number of completed LPAs by LPA type
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $lpaType
     * @return int
     */
    public function countCompletedBetweenByType(Datetime $start, Datetime $end, string $lpaType) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.type' => $lpaType
        ]);
    }

    /**
     * Get the number of completed LPAs by canSign response
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param bool $canSignValue
     * @return int
     */
    public function countCompletedBetweenByCanSign(Datetime $start, Datetime $end, bool $canSignValue) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.donor.canSign' => $canSignValue
        ]);
    }

    /**
     * Get the number of completed LPAs with at least one of the actor type defined
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $actorType
     * @return int
     */
    public function countCompletedBetweenHasActors(Datetime $start, Datetime $end, string $actorType) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.' . $actorType => [
                '$gt' => []
            ]
        ]);
    }

    /**
     * Get the number of completed LPAs with none of the actor type
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $actorType
     * @return int
     */
    public function countCompletedBetweenHasNoActors(Datetime $start, Datetime $end, string $actorType) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.' . $actorType => []
        ]);
    }

    /**
     * Get the number of completed LPAs with multiple actors of the type
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $actorType
     * @return int
     */
    public function countCompletedBetweenHasMultipleActors(Datetime $start, Datetime $end, string $actorType) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.' . $actorType => [
                '$ne' => null
            ],
            '$where' => sprintf('this.document.%s.length > 1', $actorType),
        ]);
    }

    /**
     * Get the number of completed LPAs where the donor is registering
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenDonorRegistering(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.whoIsRegistering' => 'donor'
        ]);
    }

    /**
     * Get the number of completed LPAs where an attorney is registering
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenAttorneyRegistering(Datetime $start, Datetime $end) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.whoIsRegistering' => [
                '$gt' => []
            ]
        ]);
    }

    /**
     * Get the number of completed LPAs with a case number
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param bool $hasCaseNumber
     * @return int
     */
    public function countCompletedBetweenCaseNumber(Datetime $start, Datetime $end, bool $hasCaseNumber) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'repeatCaseNumber' => ($hasCaseNumber ? [
                '$ne' => null
            ] : null),
        ]);
    }

    /**
     * Get the number of completed LPAs with the fee options set as provided
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param ?bool $reducedFeeReceivesBenefits
     * @param ?bool $reducedFeeAwardedDamages
     * @param ?bool $reducedFeeLowIncome
     * @param ?bool $reducedFeeUniversalCredit
     * @return int
     */
    public function countCompletedBetweenFeeType(Datetime $start, Datetime $end, ?bool $reducedFeeReceivesBenefits, ?bool $reducedFeeAwardedDamages, ?bool $reducedFeeLowIncome, ?bool $reducedFeeUniversalCredit) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'payment.reducedFeeReceivesBenefits' => $reducedFeeReceivesBenefits,
            'payment.reducedFeeAwardedDamages'   => $reducedFeeAwardedDamages,
            'payment.reducedFeeLowIncome'        => $reducedFeeLowIncome,
            'payment.reducedFeeUniversalCredit'  => $reducedFeeUniversalCredit,
        ]);
    }

    /**
     * Get the number of completed LPAs with the payment type defined
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $paymentType
     * @return int
     */
    public function countCompletedBetweenPaymentType(Datetime $start, Datetime $end, string $paymentType) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'payment.method' => $paymentType,
        ]);
    }

    /**
     * Get the number of completed LPAs with the attorney decisions (primary or replacement) set to the type and value provided
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $attorneyDecisionsType
     * @param string $decisionType
     * @param string $decisionValue
     * @return int
     */
    public function countCompletedBetweenWithAttorneyDecisions(Datetime $start, Datetime $end, string $attorneyDecisionsType, string $decisionType, string $decisionValue) : int
    {
        return $this->countCompletedBetween($start, $end, [
            sprintf('document.%s.%s', $attorneyDecisionsType, $decisionType) => $decisionValue
        ]);
    }

    /**
     * Get the number of completed LPAs with a trust set as an attorney
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $attorneyType
     * @return int
     */
    public function countCompletedBetweenWithTrust(Datetime $start, Datetime $end, string $attorneyType) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'document.' . $attorneyType => ['$elemMatch' => ['type' => 'trust']]
        ]);
    }

    /**
     * Get the number of completed LPAs where the certificate provider is skipped or not
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param bool $isSkipped
     * @return int
     */
    public function countCompletedBetweenCertificateProviderSkipped(Datetime $start, Datetime $end, bool $isSkipped) : int
    {
        return $this->countCompletedBetween($start, $end, [
            'metadata.' . Lpa::CERTIFICATE_PROVIDER_WAS_SKIPPED => ['$exists' => $isSkipped],
        ]);
    }
}
