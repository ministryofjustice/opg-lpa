<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use Application\Library\DateTime;
use Application\Model\DataAccess\Mongo\DateCallback;
use MongoDB\BSON\Javascript as MongoCode;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\Command;
use MongoDB\Driver\ReadPreference;
use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;

class ApiLpaCollection
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
     * @param $id
     * @param null $userId
     * @return array|null|object
     */
    public function getById($id, $userId = null)
    {
        $criteria = [
            '_id' => $id
        ];

        if (!is_null($userId)) {
            $criteria['user'] = $userId;
        }

        return $this->collection->findOne($criteria);
    }

    /**
     * @param array $criteria
     * @param array $options
     * @return \MongoDB\Driver\Cursor
     */
    public function fetch(array $criteria, array $options = [])
    {
        return $this->collection->find($criteria, $options);
    }

    /**
     * @param $userId
     * @param array $options
     * @return \MongoDB\Driver\Cursor
     */
    public function fetchByUserId($userId, array $options = [])
    {
        return $this->fetch([
            'user' => $userId,
        ], $options);
    }

    /**
     * @param Lpa $lpa
     * @return \MongoDB\InsertOneResult
     */
    public function insert(Lpa $lpa)
    {
        return $this->collection->insertOne($lpa->toArray(new DateCallback()));
    }

    /**
     * Update the LPA and the updated TS if requested to do so
     *
     * @param Lpa $lpa
     * @param $updateTimestamp
     */
    public function update(Lpa $lpa, $updateTimestamp)
    {
        //  If instrument created, record the date.
        if ($lpa->isStateCreated()) {
            if (!($lpa->createdAt instanceof \DateTime)) {
                $lpa->createdAt = new DateTime();
            }
        } else {
            $lpa->createdAt = null;
        }

        // If completed, record the date.
        if ($lpa->isStateCompleted()) {
            // If we don't already have a complete date and the LPA is locked...
            if (!($lpa->completedAt instanceof \DateTime) && $lpa->locked === true) {
                $lpa->completedAt = new DateTime();
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
            $lpa->updatedAt = new DateTime();
        }

        // updatedAt is included in the query so that data isn't overwritten
        // if the Document has changed since this process loaded it.
        $result = $this->collection->updateOne(
            ['_id' => $lpa->id, 'updatedAt' => $lastUpdated ],
            ['$set' => array_merge($lpa->toArray(new DateCallback()), ['search' => $searchField])],
            ['upsert' => false, 'multiple' => false]
        );

        // Ensure that one (and only one) document was updated.
        // If not, something when wrong.
        if ($result->getModifiedCount() !== 0 && $result->getModifiedCount() !== 1) {
            throw new RuntimeException('Unable to update LPA. This might be because "updatedAt" has changed.');
        }
    }

    /**
     * @param $lpaId
     * @param $userId
     * @return \MongoDB\UpdateResult
     */
    public function deleteById($lpaId, $userId)
    {
        //  We don't want to remove the document entirely as we need to make sure the same ID isn't reassigned
        return $this->collection->replaceOne([
            '_id' => $lpaId,
            'user' => $userId,
        ], [
            'updatedAt' => new UTCDateTime(),
        ]);
    }

    /**
     * Get the count of LPAs between two dates for the timestamp field name provided
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $timestampFieldName
     * @return int
     */
    public function countBetween(\Datetime $start, \Datetime $end, $timestampFieldName)
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
    public function countStartedForType($lpaType)
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
    public function countCreatedForType($lpaType)
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
    public function countCompletedForType($lpaType)
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
    public function countDeleted()
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
     * @return array
     *
     * The key of the return array is the number of LPAs
     * The value is the number of users with this many LPAs
     */
    public function getLpasPerUser()
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @param array $additionalCriteria
     * @return int
     */
    public function countCompletedBetween(\Datetime $start, \Datetime $end, $additionalCriteria = [])
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentEmail(\Datetime $start, \Datetime $end)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentPhone(\Datetime $start, \Datetime $end)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentPost(\Datetime $start, \Datetime $end)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentEnglish(\Datetime $start, \Datetime $end)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentWelsh(\Datetime $start, \Datetime $end)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenWithPreferences(\Datetime $start, \Datetime $end)
    {
        return $this->countCompletedBetween($start, $end, [
            'document.preference' => new Regex('.+', '')
        ]);
    }

    /**
     * Get the number of completed LPAs with instructions
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenWithInstructions(\Datetime $start, \Datetime $end)
    {
        return $this->countCompletedBetween($start, $end, [
            'document.instruction' => new Regex('.+', '')
        ]);
    }

    /**
     * Get the number of completed LPAs by LPA type
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $lpaType
     * @return int
     */
    public function countCompletedBetweenByType(\Datetime $start, \Datetime $end, $lpaType)
    {
        return $this->countCompletedBetween($start, $end, [
            'document.type' => $lpaType
        ]);
    }

    /**
     * Get the number of completed LPAs by canSign response
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $canSignValue
     * @return int
     */
    public function countCompletedBetweenByCanSign(\Datetime $start, \Datetime $end, $canSignValue)
    {
        return $this->countCompletedBetween($start, $end, [
            'document.donor.canSign' => $canSignValue
        ]);
    }

    /**
     * Get the number of completed LPAs with at least one of the actor type defined
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $actorType
     * @return int
     */
    public function countCompletedBetweenHasActors(\Datetime $start, \Datetime $end, $actorType)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $actorType
     * @return int
     */
    public function countCompletedBetweenHasNoActors(\Datetime $start, \Datetime $end, $actorType)
    {
        return $this->countCompletedBetween($start, $end, [
            'document.' . $actorType => []
        ]);
    }

    /**
     * Get the number of completed LPAs with multiple actors of the type
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $actorType
     * @return int
     */
    public function countCompletedBetweenHasMultipleActors(\Datetime $start, \Datetime $end, $actorType)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenDonorRegistering(\Datetime $start, \Datetime $end)
    {
        return $this->countCompletedBetween($start, $end, [
            'document.whoIsRegistering' => 'donor'
        ]);
    }

    /**
     * Get the number of completed LPAs where an attorney is registering
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenAttorneyRegistering(\Datetime $start, \Datetime $end)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @return int
     */
    public function countCompletedBetweenCaseNumber(\Datetime $start, \Datetime $end, $hasCaseNumber)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $reducedFeeReceivesBenefits
     * @param $reducedFeeAwardedDamages
     * @param $reducedFeeLowIncome
     * @param $reducedFeeUniversalCredit
     * @return int
     */
    public function countCompletedBetweenFeeType(\Datetime $start, \Datetime $end, $reducedFeeReceivesBenefits, $reducedFeeAwardedDamages, $reducedFeeLowIncome, $reducedFeeUniversalCredit)
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
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $paymentType
     * @return int
     */
    public function countCompletedBetweenPaymentType(\Datetime $start, \Datetime $end, $paymentType)
    {
        return $this->countCompletedBetween($start, $end, [
            'payment.method' => $paymentType,
        ]);
    }

    /**
     * Get the number of completed LPAs with the attorney decisions (primary or replacement) set to the type and value provided
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $attorneyDecisionsType
     * @param $decisionType
     * @param $decisionValue
     * @return int
     */
    public function countCompletedBetweenWithAttorneyDecisions(\Datetime $start, \Datetime $end, $attorneyDecisionsType, $decisionType, $decisionValue)
    {
        return $this->countCompletedBetween($start, $end, [
            sprintf('document.%s.%s', $attorneyDecisionsType, $decisionType) => $decisionValue
        ]);
    }

    /**
     * Get the number of completed LPAs with a trust set as an attorney
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $attorneyType
     * @return int
     */
    public function countCompletedBetweenWithTrust(\Datetime $start, \Datetime $end, $attorneyType)
    {
        return $this->countCompletedBetween($start, $end, [
            'document.' . $attorneyType => ['$elemMatch' => ['type' => 'trust']]
        ]);
    }

    /**
     * Get the number of completed LPAs where the certificate provider is skipped or not
     *
     * @param \Datetime $start
     * @param \Datetime $end
     * @param $isSkipped
     * @return int
     */
    public function countCompletedBetweenCertificateProviderSkipped(\Datetime $start, \Datetime $end, $isSkipped)
    {
        return $this->countCompletedBetween($start, $end, [
            'metadata.' . Lpa::CERTIFICATE_PROVIDER_WAS_SKIPPED => ['$exists' => $isSkipped],
        ]);
    }
}
