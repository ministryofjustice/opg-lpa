<?php

namespace MakeShared\DataModel\Lpa;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a full LPA document, plus associated metadata.
 *
 * Class Lpa
 * @package MakeShared\DataModel\Lpa
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
    const SIRIUS_PROCESSING_STATUS = 'sirius-processing-status';
    const APPLICATION_REJECTED_DATE = 'application-rejected-date';
    const APPLICATION_RECEIPT_DATE = 'application-receipt-date';
    const APPLICATION_REGISTRATION_DATE = 'application-registration-date';
    const APPLICATION_INVALID_DATE = 'application-invalid-date';
    const APPLICATION_WITHDRAWN_DATE = 'application-withdrawn-date';


    /**
     * LPA naming of Sirius processing status
     */
    const SIRIUS_PROCESSING_STATUS_RECEIVED = 'Received';
    const SIRIUS_PROCESSING_STATUS_CHECKING = 'Checking';
    const SIRIUS_PROCESSING_STATUS_RETURNED = 'Returned';

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

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
     *
     * @return mixed Mapped value.
     */
    protected function map($property, $value)
    {
        switch ($property) {
            case 'startedAt':
            case 'updatedAt':
            case 'createdAt':
            case 'completedAt':
            case 'lockedAt':
                return (($value instanceof \DateTime || is_null($value)) ? $value : new \DateTime($value));
            case 'payment':
                return (($value instanceof Payment || is_null($value)) ? $value : new Payment($value));
            case 'document':
                return (($value instanceof Document || is_null($value)) ? $value : new Document($value));
        }

        return parent::map($property, $value);
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return int|null
     */
    public function getRepeatCaseNumber()
    {
        return $this->repeatCaseNumber;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
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
    public function isWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct(): bool
    {
        return $this->isWhenReplacementAttorneyStepInHasValue(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);
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
     * @return bool
     */
    public function hasCertificateProvider(): bool
    {
        return ($this->hasDocument() && $this->getDocument()->getCertificateProvider() instanceof CertificateProvider);
    }

    /**
     * @return bool
     */
    public function hasInstructionOrPreference(): bool
    {
        return ($this->hasDocument()
            && (!is_null($this->getDocument()->getInstruction()) || !is_null($this->getDocument()->getPreference())));
    }

    // For Generation Checks

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
            if (
                $this->getPayment()->getMethod() == Payment::PAYMENT_TYPE_CHEQUE
                || $this->getPayment()->getAmount() == 0
                || $this->getPayment()->isReducedFeeUniversalCredit()
            ) {
                return true;
            } elseif (
                $this->getPayment()->getMethod() == Payment::PAYMENT_TYPE_CARD
                && $this->getPayment()->getReference() != null
            ) {
                return true;
            }
        }

        return false;
    }
}
