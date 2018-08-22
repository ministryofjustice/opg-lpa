<?php
namespace Application\Model\DataAccess\Repository\Application;

use DateTime;
use Traversable;
use Opg\Lpa\DataModel\Lpa\Lpa;

interface ApplicationRepositoryInterface {

    /**
     * Get an LPA by ID, and user ID if provided
     *
     * @param int $id
     * @param string $userId
     * @return array|null
     */
    public function getById(int $id, ?string $userId = null) : ?array;

    /**
     * Counts the number of results for the given criteria.
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria) : int;

    /**
     * @param array $criteria
     * @param array $options
     * @return Traversable
     */
    public function fetch(array $criteria, array $options = []) : Traversable;

    /**
     * @param string $userId
     * @param array $options
     * @return Traversable
     */
    public function fetchByUserId(string $userId, array $options = []) : Traversable;

    /**
     * @param Lpa $lpa
     * @return bool
     */
    public function insert(Lpa $lpa) : bool;

    /**
     * Update the LPA and the updated TS if requested to do so
     *
     * @param Lpa $lpa
     * @param bool $updateTimestamp
     * @return bool
     */
    public function update(Lpa $lpa, bool $updateTimestamp) : bool;

    /**
     * @param int $lpaId
     * @param string $userId
     * @return bool
     */
    public function deleteById(int $lpaId, string $userId) : bool;

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
    public function countBetween(Datetime $start, Datetime $end, string $timestampFieldName) : int;

    /**
     * Count the number of LPAs started but not created for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countStartedForType(string $lpaType) : int;

    /**
     * Count the number of LPAs created but not completed for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countCreatedForType(string $lpaType) : int;

    /**
     * Count the number of LPAs completed for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countCompletedForType(string $lpaType) : int;

    /**
     * Count the number of deleted LPAs
     *
     * @return int
     */
    public function countDeleted() : int;

    /**
     * Returns a list of lpa counts and user counts, in order to
     * answer questions of the form how many users have five LPAs?
     *
     * The key of the return array is the number of LPAs
     * The value is the number of users with this many LPAs
     *
     * @return array
     */
    public function getLpasPerUser() : array;

    /**
     * Get the number of completed LPAs - with additional criteria if provided
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param array $additionalCriteria
     * @return int
     */
    public function countCompletedBetween(Datetime $start, Datetime $end, array $additionalCriteria = []) : int;

    /**
     * Get the number of completed LPAs with a correspondent that has entered an email address
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentEmail(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs with a correspondent that has entered phone number
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentPhone(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs with a correspondent that has entered a postal address
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentPost(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs with a correspondent that has requested to be contacted in English
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentEnglish(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs with a correspondent that has requested to be contacted in Welsh
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenCorrespondentWelsh(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs with preferences
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenWithPreferences(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs with instructions
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenWithInstructions(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs by LPA type
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $lpaType
     * @return int
     */
    public function countCompletedBetweenByType(Datetime $start, Datetime $end, string $lpaType) : int;

    /**
     * Get the number of completed LPAs by canSign response
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param bool $canSignValue
     * @return int
     */
    public function countCompletedBetweenByCanSign(Datetime $start, Datetime $end, bool $canSignValue) : int;

    /**
     * Get the number of completed LPAs with at least one of the actor type defined
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $actorType
     * @return int
     */
    public function countCompletedBetweenHasActors(Datetime $start, Datetime $end, string $actorType) : int;

    /**
     * Get the number of completed LPAs with none of the actor type
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $actorType
     * @return int
     */
    public function countCompletedBetweenHasNoActors(Datetime $start, Datetime $end, string $actorType) : int;

    /**
     * Get the number of completed LPAs with multiple actors of the type
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $actorType
     * @return int
     */
    public function countCompletedBetweenHasMultipleActors(Datetime $start, Datetime $end, string $actorType) : int;

    /**
     * Get the number of completed LPAs where the donor is registering
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenDonorRegistering(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs where an attorney is registering
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return int
     */
    public function countCompletedBetweenAttorneyRegistering(Datetime $start, Datetime $end) : int;

    /**
     * Get the number of completed LPAs with a case number
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param bool $hasCaseNumber
     * @return int
     */
    public function countCompletedBetweenCaseNumber(Datetime $start, Datetime $end, bool $hasCaseNumber) : int;

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
    public function countCompletedBetweenFeeType(Datetime $start, Datetime $end, ?bool $reducedFeeReceivesBenefits, ?bool $reducedFeeAwardedDamages, ?bool $reducedFeeLowIncome, ?bool $reducedFeeUniversalCredit) : int;

    /**
     * Get the number of completed LPAs with the payment type defined
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $paymentType
     * @return int
     */
    public function countCompletedBetweenPaymentType(Datetime $start, Datetime $end, string $paymentType) : int;

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
    public function countCompletedBetweenWithAttorneyDecisions(Datetime $start, Datetime $end, string $attorneyDecisionsType, string $decisionType, string $decisionValue) : int;

    /**
     * Get the number of completed LPAs with a trust set as an attorney
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param string $attorneyType
     * @return int
     */
    public function countCompletedBetweenWithTrust(Datetime $start, Datetime $end, string $attorneyType) : int;

    /**
     * Get the number of completed LPAs where the certificate provider is skipped or not
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param bool $isSkipped
     * @return int
     */
    public function countCompletedBetweenCertificateProviderSkipped(Datetime $start, Datetime $end, bool $isSkipped) : int;

}
