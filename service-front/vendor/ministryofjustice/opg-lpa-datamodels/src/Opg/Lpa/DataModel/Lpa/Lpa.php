<?php

namespace Opg\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
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
    // Used to record that the certificate provider was skipped during the creation of the LPA. For stats use
    const CERTIFICATE_PROVIDER_WAS_SKIPPED = 'certificate-provider-was-skipped';
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
        return is_null($this->locked) ? false : $this->locked;
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

    //Simple property checks

    /**
     * @return bool
     */
    public function hasDocument(): bool
    {
        return $this->getDocument() instanceof Document;
    }

    /**
     * @return bool
     */
    public function hasType(): bool
    {
        return $this->hasDocument() && $this->getDocument()->getType() != null;
    }

    /**
     * @return bool
     */
    public function hasDonor(): bool
    {
        return $this->getDocument()->getDonor() instanceof Donor;
    }

    /**
     * @return bool
     */
    public function hasWhenLpaStarts(): bool
    {
        return $this->hasType()
            && $this->getDocument()->getType() == Document::LPA_TYPE_PF
            && $this->hasPrimaryAttorneyDecisions()
            && in_array($this->getDocument()->getPrimaryAttorneyDecisions()->getWhen(), [
                PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
                PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW
            ]);
    }

    /**
     * @return bool
     */
    public function hasLifeSustaining(): bool
    {
        return $this->hasType()
            && $this->getDocument()->getType() == Document::LPA_TYPE_HW
            && $this->hasPrimaryAttorneyDecisions()
            && is_bool($this->getDocument()->getPrimaryAttorneyDecisions()->isCanSustainLife());
    }

    /**
     * @param int|null $index
     * @return bool
     */
    public function hasPrimaryAttorney(?int $index = null): bool
    {
        if ($this->hasDocument()) {
            if ($index === null) {
                return count($this->getDocument()->getPrimaryAttorneys()) > 0;
            } else {
                return array_key_exists($index, $this->getDocument()->getPrimaryAttorneys())
                    && $this->getDocument()->getPrimaryAttorneys()[$index] instanceof AbstractAttorney;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasMultiplePrimaryAttorneys(): bool
    {
        return $this->hasPrimaryAttorney() && count($this->getDocument()->getPrimaryAttorneys()) > 1;
    }

    /**
     * @return bool
     */
    public function hasPrimaryAttorneyDecisions(): bool
    {
        return $this->hasDocument()
            && $this->getDocument()->getPrimaryAttorneyDecisions() instanceof PrimaryAttorneyDecisions;
    }

    /**
     * @return bool
     */
    public function isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally(): bool
    {
        return $this->isHowPrimaryAttorneysMakeDecisionHasValue(
            AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
        );
    }

    /**
     * @return bool
     */
    public function isHowPrimaryAttorneysMakeDecisionJointly(): bool
    {
        return $this->isHowPrimaryAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
    }

    /**
     * @return bool
     */
    public function isHowPrimaryAttorneysMakeDecisionDepends(): bool
    {
        return $this->isHowPrimaryAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
    }

    /**
     * @param null|string $valueToCheck
     * @return bool
     */
    public function isHowPrimaryAttorneysMakeDecisionHasValue(?string $valueToCheck = null): bool
    {
        if ($this->hasDocument()) {
            $decisions = $this->getDocument()->getPrimaryAttorneyDecisions();

            if ($decisions instanceof AbstractDecisions) {
                if (!is_null($valueToCheck)) {
                    //  Check the specific value
                    return ($decisions->getHow() == $valueToCheck);
                }

                return in_array($decisions->getHow(), [
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
                    AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                ]);
            }
        }

        return false;
    }

    /**
     * @param int|null $index
     * @return bool
     */
    public function hasReplacementAttorney(?int $index = null): bool
    {
        if ($this->hasDocument()) {
            $replacementAttorneys = $this->getDocument()->getReplacementAttorneys();

            if (is_array($replacementAttorneys)) {
                if (!is_null($index)) {
                    return (isset($replacementAttorneys[$index])
                        && $replacementAttorneys[$index] instanceof AbstractAttorney);
                }

                return (count($replacementAttorneys) > 0);
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasMultipleReplacementAttorneys(): bool
    {
        return ($this->hasReplacementAttorney() && count($this->getDocument()->getReplacementAttorneys()) > 1);
    }

    /**
     * @return bool
     */
    public function isWhenReplacementAttorneyStepInDepends(): bool
    {
        return $this->isWhenReplacementAttorneyStepInHasValue(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS);
    }

    /**
     * @return bool
     */
    public function isWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct(): bool
    {
        return $this->isWhenReplacementAttorneyStepInHasValue(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);
    }

    /**
     * @return bool
     */
    public function isWhenReplacementAttorneyStepInWhenFirstPrimaryUnableAct(): bool
    {
        return $this->isWhenReplacementAttorneyStepInHasValue(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST);
    }

    /**
     * @param null|string $valueToCheck
     * @return bool
     */
    public function isWhenReplacementAttorneyStepInHasValue(?string $valueToCheck = null): bool
    {
        if ($this->hasDocument()) {
            $decisions = $this->getDocument()->getReplacementAttorneyDecisions();

            if ($decisions instanceof AbstractDecisions) {
                if (!is_null($valueToCheck)) {
                    //  Check the specific value
                    return ($decisions->getWhen() == $valueToCheck);
                }

                return in_array($decisions->getWhen(), [
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST,
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS
                ]);
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isHowReplacementAttorneysMakeDecisionJointlyAndSeverally(): bool
    {
        return $this->isHowReplacementAttorneysMakeDecisionHasValue(
            AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
        );
    }

    /**
     * @return bool
     */
    public function isHowReplacementAttorneysMakeDecisionJointly(): bool
    {
        return $this->isHowReplacementAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
    }

    /**
     * @return bool
     */
    public function isHowReplacementAttorneysMakeDecisionDepends(): bool
    {
        return $this->isHowReplacementAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
    }

    /**
     * @param null|string $valueToCheck
     * @return bool
     */
    public function isHowReplacementAttorneysMakeDecisionHasValue(?string $valueToCheck = null): bool
    {
        if ($this->hasMultipleReplacementAttorneys()) {
            $decisions = $this->getDocument()->getReplacementAttorneyDecisions();

            if ($decisions instanceof AbstractDecisions) {
                if (!is_null($valueToCheck)) {
                    //  Check the specific value
                    return ($decisions->getHow() == $valueToCheck);
                }

                return in_array($decisions->getHow(), [
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
                    AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                ]);
            }
        }

        return false;
    }

    /**
     * @param null|string $whichGroup
     * @return bool
     */
    public function hasTrustCorporation(?string $whichGroup = null): bool
    {
        if ($this->hasDocument()) {
            //  By default we will check all the attorneys
            $primaryAttorneys = $this->getDocument()->getPrimaryAttorneys();
            $replacementAttorneys = $this->getDocument()->getReplacementAttorneys();
            $attorneys = array_merge($primaryAttorneys, $replacementAttorneys);

            if ($whichGroup == 'primary') {
                $attorneys = $primaryAttorneys;
            } elseif ($whichGroup == 'replacement') {
                $attorneys = $replacementAttorneys;
            }

            foreach ($attorneys as $attorney) {
                if ($attorney instanceof TrustCorporation) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasCertificateProvider(): bool
    {
        return ($this->hasDocument() && $this->getDocument()->getCertificateProvider() instanceof CertificateProvider);
    }

    /**
     * @return bool
     */
    public function hasCertificateProviderSkipped(): bool
    {
        return ($this->hasDocument() && array_key_exists(Lpa::CERTIFICATE_PROVIDER_SKIPPED, $this->getMetadata()));
    }

    /**
     * @return bool
     */
    public function hasCorrespondent(): bool
    {
        return ($this->hasDocument() && $this->getDocument()->getCorrespondent() instanceof Correspondence);
    }

    /**
     * @param int|null $index
     * @return bool
     */
    public function hasPeopleToNotify(?int $index = null): bool
    {
        if ($this->hasDocument()) {
            $peopleToNotify = $this->getDocument()->getPeopleToNotify();

            if (count($peopleToNotify) > 0) {
                if (!is_null($index)) {
                    return ($peopleToNotify[$index] instanceof NotifiedPerson);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasInstructionOrPreference(): bool
    {
        return ($this->hasDocument()
            && (!is_null($this->getDocument()->getInstruction()) || !is_null($this->getDocument()->getPreference())));
    }

    /**
     * @return bool
     */
    public function hasApplicant(): bool
    {
        return ($this->hasDocument() && ($this->getDocument()->getWhoIsRegistering() == 'donor'
                || (is_array($this->getDocument()->getWhoIsRegistering())
                    && count($this->getDocument()->getWhoIsRegistering()) > 0)));
    }

    // For Generation Checks

    /**
     * Can a LP1 currently be generated.
     *
     * @return bool
     */
    public function canGenerateLP1()
    {
        return $this->isStateCreated();
    }

    /**
     * Can a LP3 currently be generated.
     *
     * @return bool
     */
    public function canGenerateLP3()
    {
        return ($this->isStateCompleted() && count($this->getDocument()->getPeopleToNotify()) > 0);
    }

    /**
     * Can a LPA120 currently be generated.
     *
     * @return bool
     */
    public function canGenerateLPA120()
    {
        return ($this->isStateCompleted() && $this->isEligibleForFeeReduction());
    }

    // State Checks

    /**
     * Checks if the LPA has been started (from the perspective of the business)
     *
     * @return bool
     */
    public function isStateStarted()
    {
        return !$this->validate(['id'])->hasErrors();
    }

    /**
     * Checks if the LPA is Created (from the perspective of the business)
     *
     * @return bool
     */
    public function isStateCreated()
    {
        return $this->isStateStarted() && $this->hasFinishedCreation();
    }

    /**
     * Checks if the LPA is Complete (from the perspective of the business)
     *
     * @return bool
     */
    public function isStateCompleted()
    {
        return $this->isStateCreated() && $this->isPaymentResolved();
    }

    // Below are the functions copied from the front2 model.

    /**
     * Lpa all required properties has value to qualify as an Instrument
     *
     * @return bool
     */
    public function hasFinishedCreation(): bool
    {
        //  For an LPA instrument to be considered complete, the LPA must...
        //  Have a Certificate provider, primary attorney(s) and
        //  instructions or preferences to not be null (they can be blank)...
        $complete = $this->hasCertificateProvider()
            && $this->hasPrimaryAttorney()
            && $this->hasInstructionOrPreference();

        // AND if there is > 1 Primary Attorney
        if ($this->hasMultiplePrimaryAttorneys()) {
            // we need how Primary Attorney make decisions.
            $complete = $complete && $this->isHowPrimaryAttorneysMakeDecisionHasValue();

            // AND if we also have > 0 Replacement Attorneys...
            if ($this->hasReplacementAttorney()) {
                if ($this->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
                    // AND Primary Attorney are J&S...

                    // we need to know when Replacement Attorneys Step In.
                    $complete = $complete && $this->isWhenReplacementAttorneyStepInHasValue();

                    // If the Replacement Attorneys don't step in until all the Primary Attorney are gone...
                    if ($this->isWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct()) {
                        // AND we have > 1 Replacement Attorneys
                        if ($this->hasMultipleReplacementAttorneys()) {
                            // We also need to know how they will make decision when they do step in.
                            $complete = $complete && $this->isHowReplacementAttorneysMakeDecisionHasValue();
                        }
                    }
                } elseif ($this->isHowPrimaryAttorneysMakeDecisionJointly()) {
                    // AND Primary Attorney are J...

                    // AND we have > 1 Replacement Attorneys
                    if ($this->hasMultipleReplacementAttorneys()) {
                        // We need to know how Replacement Attorneys will make decisions.
                        $complete = $complete && $this->isHowReplacementAttorneysMakeDecisionHasValue();
                    }
                }
            }
        } elseif ($this->hasMultipleReplacementAttorneys()) {
            // Else if there are > 0  Replacement Attorneys (but only 1 Primary Attorney)
            $complete = $complete && $this->isHowReplacementAttorneysMakeDecisionHasValue();
        }

        return $complete;
    }

    /**
     * LPA Instrument is created and created date is set
     *
     * @return bool
     */
    public function hasCreated(): bool
    {
        return ($this->hasFinishedCreation() && $this->getCreatedAt() !== null);
    }

    /**
     * is the donor eligible for fee reduction due to having benefit, damage, income or universal credit.
     *
     * @return bool
     */
    public function isEligibleForFeeReduction(): bool
    {
        if (!$this->getPayment() instanceof Payment) {
            return false;
        }

        return (($this->getPayment()->isReducedFeeReceivesBenefits()
                && $this->getPayment()->isReducedFeeAwardedDamages())
            || $this->getPayment()->isReducedFeeUniversalCredit()
            || $this->getPayment()->isReducedFeeLowIncome());
    }

    /**
     * Payment either paid online or offline, or no payment to be taken.
     * @return bool
     */
    public function isPaymentResolved(): bool
    {
        if ($this->getPayment() instanceof Payment) {
            //  If the payment method is cheque or the amount is zero or the payment is reduced due to UC,
            //  then the payment is considered resolved
            if ($this->getPayment()->getMethod() == Payment::PAYMENT_TYPE_CHEQUE
                || $this->getPayment()->getAmount() == 0
                || $this->getPayment()->isReducedFeeUniversalCredit()) {
                return true;
            } elseif ($this->getPayment()->getMethod() == Payment::PAYMENT_TYPE_CARD
                && $this->getPayment()->getReference() != null) {
                return true;
            }
        }

        return false;
    }
}
