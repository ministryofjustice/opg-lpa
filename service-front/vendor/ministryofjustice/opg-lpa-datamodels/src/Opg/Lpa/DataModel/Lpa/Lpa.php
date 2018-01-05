<?php

namespace Opg\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a full LPA document, plus associated metadata.
 *
 * Class Lpa
 * @package Opg\Lpa\DataModel\Lpa
 */
class Lpa extends AbstractData
{
    /**
     * Metadata constants
     */
    const REPLACEMENT_ATTORNEYS_CONFIRMED = 'replacement-attorneys-confirmed';
    const CERTIFICATE_PROVIDER_SKIPPED = 'certificate-provider-skipped';
    const PEOPLE_TO_NOTIFY_CONFIRMED = 'people-to-notify-confirmed';
    const REPEAT_APPLICATION_CONFIRMED = 'repeat-application-confirmed';
    const APPLY_FOR_FEE_REDUCTION = 'apply-for-fee-reduction';
    const INSTRUCTION_CONFIRMED = 'instruction-confirmed';
    const ANALYTICS_RETURN_COUNT = 'analyticsReturnCount';

    /**
     * @var int The LPA identifier.
     */
    protected $id;

    /**
     * @var \DateTime the LPA was started.
     */
    protected $startedAt;

    /**
     * This means 'created' in the business sense, which means when the LPA instrument is finished.
     *
     * @var \DateTime the LPA was created.
     */
    protected $createdAt;

    /**
     * @var \DateTime the LPA was last updated.
     */
    protected $updatedAt;

    /**
     * @var \DateTime|null When the LPA was last updated AND it's status (at the moment in time) was complete.
     */
    protected $completedAt;

    /**
     * @var \DateTime|null DateTime the LPA was locked.
     */
    protected $lockedAt;

    /**
     * @var string LPA's owner User identifier.
     */
    protected $user;

    /**
     * @var Payment status.
     */
    protected $payment;

    /**
     * @var bool Flag to record whether the 'Who Are You' question has been answered with regards to this LPA.
     */
    protected $whoAreYouAnswered;

    /**
     * @var bool Is this LPA locked. i.e. read-only.
     */
    protected $locked;

    /**
     * @var int Reference to another LPA on which this LPA is based.
     */
    protected $seed;

    /**
     * @var int|null If this is a repeat LPA application, the relevant case number.
     */
    protected $repeatCaseNumber;

    /**
     * @var Document All the details making up the LPA document.
     */
    protected $document;

    /**
     * @var array Metadata relating to the LPA. Clients can use this value however they wish.
     */
    protected $metadata = [];

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('id', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'int'
            ]),
            new Assert\Range([
                'min' => 0,
                'max' => 99999999999
            ]),
        ]);

        $metadata->addPropertyConstraints('startedAt', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('createdAt', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('updatedAt', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('completedAt', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('lockedAt', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('user', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'xdigit'
            ]),
            new Assert\Length([
                'min' => 32,
                'max' => 32
            ]),
        ]);

        $metadata->addPropertyConstraints('payment', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Payment\Payment'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('whoAreYouAnswered', [
            new Assert\NotNull,
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);

        $metadata->addPropertyConstraints('locked', [
            new Assert\NotNull,
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);

        $metadata->addPropertyConstraints('seed', [
            new Assert\Type([
                'type' => 'int'
            ]),
            new Assert\Range([
                'min' => 0,
                'max' => 99999999999
            ]),
        ]);

        $metadata->addPropertyConstraints('repeatCaseNumber', [
            new Assert\Type([
                'type' => 'int'
            ]),
        ]);

        $metadata->addPropertyConstraints('document', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Document\Document'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('metadata', [
            new Assert\NotNull,
            new Assert\Type([
                'type' => 'array'
            ]),
            new CallbackConstraintSymfony(function ($value, ExecutionContextInterface $context) {
                // Max allowed size when JSON encoded in bytes.
                $bytes = 1024 * 1024 * 1;

                // Put a $bytes limit (when JSON encoded) on the metadata array.
                if (is_array($value) && strlen(json_encode($value)) >  $bytes) {
                    $context->buildViolation('must-be-less-than-or-equal:' . $bytes . '-bytes')->addViolation();
                }
            })
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map($property, $v)
    {
        switch ($property) {
            case 'startedAt':
            case 'updatedAt':
            case 'createdAt':
            case 'completedAt':
            case 'lockedAt':
                return (($v instanceof \DateTime || is_null($v)) ? $v : new \DateTime($v));
            case 'payment':
                return (($v instanceof Payment || is_null($v)) ? $v : new Payment($v));
            case 'document':
                return (($v instanceof Document || is_null($v)) ? $v : new Document($v));
        }

        return parent::map($property, $v);
    }

    /**
     * @param callable|null $dateCallback
     * @return array
     */
    public function toArray(callable $dateCallback = null)
    {
        $data = parent::toArray($dateCallback);

        //  If a date callback was used then convert the id value to _id
        if (is_callable($dateCallback)) {
            // Rename 'id' to '_id' (keeping it at the beginning of the array)
            $data = ['_id' => $data['id']] + $data;

            unset($data['id']);
        }

        return $data;
    }

    /**
     * Return an abbreviated (summary) version of the LPA.
     *
     * @return array
     */
    public function abbreviatedToArray()
    {
        $data = $this->toArray();

        // Include these top level fields...
        $data = array_intersect_key($data, array_flip([
            'id',
            'lockedAt',
            'startedAt',
            'updatedAt',
            'createdAt',
            'completedAt',
            'user',
            'locked',
            'document',
            'metadata'
        ]));

        // Include these document level fields...
        $data['document'] = array_intersect_key($data['document'], array_flip([
            'donor', 'type'
        ]));

        return $data;
    }

    /**
     * Perform a deep compare of this LPA against a supplied comparison
     *
     * @param $comparisonLpa
     * @return bool
     */
    public function equals($comparisonLpa)
    {
        return $this == $comparisonLpa;
    }

    /**
     * Perform a deep compare of this LPA's document against a supplied comparison ignoring the metadata properties
     *
     * @param $comparisonLpa
     * @return bool
     */
    public function equalsIgnoreMetadata($comparisonLpa)
    {
        return $comparisonLpa !== null && $this->document == $comparisonLpa->document;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id): Lpa
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartedAt(): \DateTime
    {
        return $this->startedAt;
    }

    /**
     * @param \DateTime $startedAt
     * @return $this
     */
    public function setStartedAt(\DateTime $startedAt): Lpa
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt): Lpa
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt): Lpa
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    /**
     * @param \DateTime|null $completedAt
     * @return $this
     */
    public function setCompletedAt($completedAt)
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLockedAt()
    {
        return $this->lockedAt;
    }

    /**
     * @param \DateTime|null $lockedAt
     * @return $this
     */
    public function setLockedAt($lockedAt)
    {
        $this->lockedAt = $lockedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return $this
     */
    public function setUser(string $user): Lpa
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     * @return $this
     */
    public function setPayment($payment): Lpa
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWhoAreYouAnswered(): bool
    {
        return $this->whoAreYouAnswered;
    }

    /**
     * @param bool $whoAreYouAnswered
     * @return $this
     */
    public function setWhoAreYouAnswered(bool $whoAreYouAnswered): Lpa
    {
        $this->whoAreYouAnswered = $whoAreYouAnswered;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     * @return $this
     */
    public function setLocked(bool $locked): Lpa
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @return int
     */
    public function getSeed()
    {
        return $this->seed;
    }

    /**
     * @param int $seed
     * @return $this
     */
    public function setSeed($seed): Lpa
    {
        $this->seed = $seed;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRepeatCaseNumber()
    {
        return $this->repeatCaseNumber;
    }

    /**
     * @param int|null $repeatCaseNumber
     * @return $this
     */
    public function setRepeatCaseNumber($repeatCaseNumber)
    {
        $this->repeatCaseNumber = $repeatCaseNumber;

        return $this;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param Document $document
     * @return $this
     */
    public function setDocument($document): Lpa
    {
        $this->document = $document;

        return $this;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     * @return $this
     */
    public function setMetadata(array $metadata): Lpa
    {
        $this->metadata = $metadata;

        return $this;
    }
}
