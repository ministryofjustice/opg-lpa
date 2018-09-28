<?php
namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Traversable;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\IsNull;
use Zend\Db\Sql\Predicate\IsNotNull;
use Zend\Db\Metadata\Source\Factory as DbMetadataFactory;
use Application\Model\DataAccess\Repository\Application as ApplicationRepository;
use Application\Model\DataAccess\Repository\Application\LockedException;
use Application\Library\DateTime as MillisecondDateTime;

class ApplicationData extends AbstractBase implements ApplicationRepository\ApplicationRepositoryInterface
{

    const APPLICATIONS_TABLE = 'applications';

    /**
     * The columns in the Postgres database
     */
    const TABLE_COLUMNS = ['id', 'user', 'updatedAt', 'startedAt', 'createdAt', 'completedAt', 'lockedAt', 'locked',
                            'whoAreYouAnswered', 'seed', 'repeatCaseNumber', 'document', 'payment', 'metadata'];


    /**
     * Maps LPA object fields to Postgres' fields.
     *
     * @param Lpa $lpa
     * @return array
     */
    private function mapLpaToPostgres(Lpa $lpa) : array
    {
        // Filter out un-allowed columns.
        $data = array_intersect_key($lpa->toArray(), array_flip(self::TABLE_COLUMNS));

        // Convert these fields to JSON
        $data['document']   = is_null($data['document']) ? null : json_encode($data['document']);
        $data['payment']    = is_null($data['payment']) ? null : json_encode($data['payment']);
        $data['metadata']   = is_null($data['metadata']) ? null : json_encode($data['metadata']);

        return $data;
    }

    /**
     * Maps data from Postgres back into an array format that the LPA DataModel can consume.
     *
     * @param array $data
     * @return array
     */
    private function mapPostgresToLpaCompatible(array $data) : array
    {
        return array_merge($data,[
            'document'  => is_null($data['document']) ? null : json_decode($data['document'], true),
            'payment'   => is_null($data['payment']) ? null : json_decode($data['payment'], true),
            'metadata'  => is_null($data['metadata']) ? null : json_decode($data['metadata'], true),
        ]);
    }


    //------------------------------------------

    /**
     * Get an LPA by ID, and user ID if provided
     *
     * @param int $id
     * @param string $userId
     * @return array|null
     */
    public function getById(int $id, ?string $userId = null) : ?array
    {
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::APPLICATIONS_TABLE);

        $select->where(['id' => $id]);
        if (is_string($userId)) {
            $select->where(['user' => $userId]);
        }

        $select->limit(1);

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$result->isQueryResult() || $result->count() != 1) {
            return null;
        }

        return $this->mapPostgresToLpaCompatible($result->current());
    }

    /**
     * Counts the number of results for the given criteria.
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria) : int
    {
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::APPLICATIONS_TABLE);

        $select->columns(['count' => new Expression('count(*)')]);

        if (isset($criteria['search'])) {
            $select->where([new Expression("search ~* '{$criteria['search']['$regex']}'")]);
            unset($criteria['search']);
        }

        $select->where($criteria);

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$result->isQueryResult() || $result->count() != 1) {
            return 0;
        }

        return $result->current()['count'];
    }

    /**
     * @param array $criteria
     * @param array $options
     * @return Traversable
     */
    public function fetch(array $criteria, array $options = []) : Traversable
    {
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::APPLICATIONS_TABLE);

        if (isset($criteria['search'])) {
            $select->where([new Expression("search ~* '{$criteria['search']['$regex']}'")]);
            unset($criteria['search']);
        }

        $select->where($criteria);

        if (isset($options['skip']) && $options['skip'] !== 0) {
            $select->offset($options['skip']);
        }

        if (isset($options['limit'])) {
            $select->limit($options['limit']);
        }

        if (isset($options['sort'])) {
            foreach($options['sort'] as $field=>$direction){
                $direction = ($direction === 1) ? 'ASC' : 'DESC';
                $select->order("$field $direction");
            }
        }

        $results = $sql->prepareStatementForSqlObject($select)->execute();

        foreach ($results as $result) {
            yield $this->mapPostgresToLpaCompatible($result);
        }
    }

    /**
     * @param string $userId
     * @param array $options
     * @return Traversable
     */
    public function fetchByUserId(string $userId, array $options = []) : Traversable
    {
        return $this->fetch(['user' => $userId], $options);
    }

    /**
     * @param Lpa $lpa
     * @return bool
     *
     */
    public function insert(Lpa $lpa) : bool
    {
        $sql = new Sql($this->getZendDb());
        $insert = $sql->insert(self::APPLICATIONS_TABLE);

        $data = $this->mapLpaToPostgres($lpa);
        $insert->columns(array_keys($data));
        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

        // If something goes wrong, allow the exception to be thrown
        $statement->execute();

        return true;
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
            if (!($lpa->getCreatedAt() instanceof DateTime)) {
                $lpa->setCreatedAt(new MillisecondDateTime());
            }
        } else {
            $lpa->setCreatedAt(null);
        }

        // If completed, record the date.
        if ($lpa->isStateCompleted()) {
            // If we don't already have a complete date and the LPA is locked...
            if (!($lpa->getCompletedAt() instanceof DateTime) && $lpa->isLocked()) {
                $lpa->setCompletedAt(new MillisecondDateTime());
            }
        } else {
            $lpa->setCompletedAt(null);
        }

        // If there's a donor, populate the free text search field
        $searchField = null;

        if ($lpa->getDocument()->getDonor() != null) {
            $searchField = (string)$lpa->getDocument()->getDonor()->getName();
        }

        $lastUpdated = $lpa->getUpdatedAt()->format(self::TIME_FORMAT);

        if ($updateTimestamp === true) {
            // Record the time we updated the document.
            $lpa->setUpdatedAt(new MillisecondDateTime());
        }

        //------------------------------------------

        $sql = new Sql($this->getZendDb());
        $update = $sql->update(self::APPLICATIONS_TABLE);

        $update->where([
            'id'        => $lpa->getId(),
            'updatedAt' => $lastUpdated,    // Sense check to ensure we're not working with stale data
        ]);

        $data = $this->mapLpaToPostgres($lpa);
        unset($data['id']); // Un-needed

        $data['search'] = $searchField ?: null;

        $update->set($data);

        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();

        return $results->getAffectedRows() === 1;
    }

    /**
     * @param int $lpaId
     * @param string $userId
     * @return bool
     */
    public function deleteById(int $lpaId, string $userId) : bool
    {
        $sql = new Sql($this->getZendDb());
        $update = $sql->update(self::APPLICATIONS_TABLE);

        $update->where([
            'id'    => $lpaId,
            'user'  => $userId,
        ]);

        //---

        /**
         * We pull the full column list from Postgres here to ensure we set all of the to null.
         * (This isn't efficient for bulk deletes but is fine until we see any issues)
         */
        $metadata = DbMetadataFactory::createSourceFromAdapter($this->getZendDb());
        $table = $metadata->getTable(self::APPLICATIONS_TABLE);

        $data = [];

        // Set every column to null
        foreach ($table->getColumns() as $column) {
            $data[$column->getName()] = null;
        }


        unset($data['id']); // We want to keep this
        $data['updatedAt'] = gmdate(self::TIME_FORMAT); // We want to keep and update this.

        //--

        $update->set($data);

        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();

        return $results->getAffectedRows() === 1;
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
        return $this->count([
            new Operator($timestampFieldName, Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $start->format('c')),
            new Operator($timestampFieldName, Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $end->format('c')),
        ]);
    }

    /**
     * Count the number of LPAs started but not created for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countStartedForType(string $lpaType) : int
    {
        return $this->count([
            new IsNotNull('startedAt'),
            new IsNull('createdAt'),
            new Expression("document ->> 'type' = ?", $lpaType),
        ]);
    }

    /**
     * Count the number of LPAs created but not completed for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countCreatedForType(string $lpaType) : int
    {
        return $this->count([
            new IsNotNull('createdAt'),
            new IsNull('completedAt'),
            new Expression("document ->> 'type' = ?", $lpaType),
        ]);
    }

    /**
     * Count the number of LPAs completed for a given LPA type
     *
     * @param $lpaType
     * @return int
     */
    public function countCompletedForType(string $lpaType) : int
    {
        return $this->count([
            new IsNotNull('completedAt'),
            new Expression("document ->> 'type' = ?", $lpaType),
        ]);
    }

    /**
     * Count the number of deleted LPAs
     *
     * @return int
     */
    public function countDeleted() : int
    {
        return $this->count([
            new IsNull('user'),
        ]);
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
        $adapter = $this->getZendDb();

        /*
         The query is:

            WITH lpa_counts AS(
                SELECT "user", count(*) AS "lpa_count" FROM "applications" WHERE "user" IS NOT NULL GROUP BY "user"
            )
            SELECT lpa_count, count(*) AS "user_count" FROM lpa_counts GROUP BY lpa_count
         */

        $sql = new Sql($adapter);

        $selectOne = $sql->select(self::APPLICATIONS_TABLE);
        $selectOne->columns(['user', 'lpa_count' => new Expression('count(*)')]);
        $selectOne->where([new IsNotNull('user')]);
        $selectOne->group('user');

        $selectTwo = $sql->select('lpa_counts');
        $selectTwo->columns(['lpa_count', 'user_count' => new Expression('count(*)')]);
        $selectTwo->group('lpa_count');
        $selectTwo->order('lpa_count DESC');

        $query = 'WITH lpa_counts AS('.$sql->buildSqlString($selectOne).') '.$sql->buildSqlString($selectTwo);

        $results = $adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->toArray();

        /*
         * This creates an array where:
         *  key = a number or LPAs
         *  value = the number of users with that number of LPAs.
         *
         */
        return array_combine(
            array_column($results, 'lpa_count'),
            array_column($results, 'user_count')
        );
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
        return $this->count(array_merge([
            new Operator('completedAt', Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $start->format('c')),
            new Operator('completedAt', Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $end->format('c')),
        ], $additionalCriteria));
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
            new Expression("document -> 'correspondent' ->> 'email' IS NOT NULL")
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
            new Expression("document -> 'correspondent' ->> 'phone' IS NOT NULL")
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
            new Expression("(document -> 'correspondent' ->> 'contactByPost')::BOOLEAN = TRUE")
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
            new Expression("(document -> 'correspondent' ->> 'contactInWelsh')::BOOLEAN = FALSE")
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
            new Expression("(document -> 'correspondent' ->> 'contactInWelsh')::BOOLEAN = TRUE")
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
            new Expression("document ->> 'preference' <> ''")
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
            new Expression("document ->> 'instruction' <> ''")
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
            new Expression("document ->> 'type' = ?", $lpaType)
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
        $canSign = ($canSignValue) ? 'TRUE' : 'FALSE';

        return $this->countCompletedBetween($start, $end, [
            new Expression("(document -> 'donor' ->> 'canSign')::BOOLEAN = {$canSign}")
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
            new Expression("json_array_length((document ->> ?)::JSON) > 0", $actorType)
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
            new Expression("json_array_length((document ->> ?)::JSON) = 0", $actorType)
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
            new Expression("json_array_length((document ->> ?)::JSON) > 1", $actorType)
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
            new Expression("document ->> 'whoIsRegistering' = ?", 'donor')
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
            new Expression("document ->> 'whoIsRegistering' <> ?", 'donor')
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
        if ($hasCaseNumber) {
            return $this->countCompletedBetween($start, $end, [
                new IsNotNull('repeatCaseNumber')
            ]);
        } else {
            return $this->countCompletedBetween($start, $end, [
                new IsNull('repeatCaseNumber')
            ]);
        }
    }

    /**
     * Get the number of completed LPAs with the fee options set as provided
     *
     * @param Datetime $start
     * @param Datetime $end
     * @param bool $reducedFeeReceivesBenefits
     * @param bool $reducedFeeAwardedDamages
     * @param bool $reducedFeeLowIncome
     * @param bool $reducedFeeUniversalCredit
     * @return int
     */
    public function countCompletedBetweenFeeType(Datetime $start, Datetime $end, ?bool $reducedFeeReceivesBenefits, ?bool $reducedFeeAwardedDamages, ?bool $reducedFeeLowIncome, ?bool $reducedFeeUniversalCredit) : int
    {
        // Map the values
        $reducedFeeReceivesBenefits = (is_null($reducedFeeReceivesBenefits)) ? 'IS NULL' : (($reducedFeeReceivesBenefits) ? '= TRUE' : '= FALSE');
        $reducedFeeAwardedDamages   = (is_null($reducedFeeAwardedDamages)) ? 'IS NULL'   : (($reducedFeeAwardedDamages) ? '= TRUE' : '= FALSE');
        $reducedFeeLowIncome        = (is_null($reducedFeeLowIncome)) ? 'IS NULL'        : (($reducedFeeLowIncome) ? '= TRUE' : '= FALSE');
        $reducedFeeUniversalCredit  = (is_null($reducedFeeUniversalCredit)) ? 'IS NULL'  : (($reducedFeeUniversalCredit) ? '= TRUE' : '= FALSE');

        return $this->countCompletedBetween($start, $end, [
            new Expression("(payment ->> 'reducedFeeReceivesBenefits')::BOOLEAN " . $reducedFeeReceivesBenefits),
            new Expression("(payment ->> 'reducedFeeAwardedDamages')::BOOLEAN " . $reducedFeeAwardedDamages),
            new Expression("(payment ->> 'reducedFeeLowIncome')::BOOLEAN " . $reducedFeeLowIncome),
            new Expression("(payment ->> 'reducedFeeUniversalCredit')::BOOLEAN " . $reducedFeeUniversalCredit),
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
            new Expression("payment ->> 'method' = ?", $paymentType)
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
            new Expression("document -> '{$attorneyDecisionsType}' ->> '{$decisionType}' = ?", $decisionValue)
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
            new Expression("document -> '{$attorneyType}' @> ?", '[{"type": "trust"}]')
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
            new Expression("metadata @> ?", json_encode([Lpa::CERTIFICATE_PROVIDER_WAS_SKIPPED => true]))
        ]);
    }

}
