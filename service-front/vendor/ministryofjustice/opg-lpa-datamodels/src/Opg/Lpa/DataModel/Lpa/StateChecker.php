<?php

namespace Opg\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

/**
 * Provides a library of methods for checking the state of
 * an LPA object from a business domain perspective.
 *
 * Class StateChecker
 * @package Opg\Lpa\DataModel\Lpa
 */
class StateChecker
{
    /**
     * LPA instance to apply checks to.
     *
     * @var LPA
     */
    protected $lpa;

    /**
     * @param Lpa $lpa LPA instance to apply checks to.
     */
    public function __construct(Lpa $lpa = null)
    {
        $this->lpa = $lpa;
    }

    /**
     * Return the LPA.
     *
     * @return LPA
     */
    public function getLpa()
    {
        return $this->lpa;
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
        return ($this->isStateCompleted() && count($this->lpa->getDocument()->getPeopleToNotify()) > 0);
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
        return is_int($this->lpa->getId());
    }

    /**
     * Checks if the LPA is Created (from the perspective of the business)
     *
     * @return bool
     */
    public function isStateCreated()
    {
        return $this->isStateStarted() && $this->lpaHasFinishedCreation();
    }

    /**
     * Checks if the LPA is Complete (from the perspective of the business)
     *
     * @return bool
     */
    public function isStateCompleted()
    {
        return $this->isStateCreated() && $this->paymentResolved();
    }

    // Below are the functions copied from the front2 model.

    /**
     * Payment either paid online or offline, or no payment to be taken.
     * @return boolean
     */
    protected function paymentResolved()
    {
        if ($this->lpa->getPayment() instanceof Payment) {
            //  If the payment method is cheque or the amount is zero or the payment is reduced due to UC,
            //  then the payment is considered resolved
            if ($this->lpa->getPayment()->getMethod() == Payment::PAYMENT_TYPE_CHEQUE
                || $this->lpa->getPayment()->getAmount() == 0
                || $this->lpa->getPayment()->isReducedFeeUniversalCredit()) {
                return true;
            } elseif ($this->lpa->getPayment()->getMethod() == Payment::PAYMENT_TYPE_CARD
                && $this->lpa->getPayment()->getReference() != null) {
                return true;
            }
        }

        return false;
    }

    /**
     * is the donor eligible for fee reduction due to having benefit, damage, income or universal credit.
     *
     * @return boolean
     */
    public function isEligibleForFeeReduction()
    {
        if (!$this->lpa->getPayment() instanceof Payment) {
            return false;
        }

        return (($this->lpa->getPayment()->isReducedFeeReceivesBenefits()
                && $this->lpa->getPayment()->isReducedFeeAwardedDamages())
                || $this->lpa->getPayment()->isReducedFeeUniversalCredit()
                || $this->lpa->getPayment()->isReducedFeeLowIncome());
    }

    protected function isWhoAreYouAnswered()
    {
        return ($this->lpaHasCorrespondent() && $this->lpa->isWhoAreYouAnswered() == true);
    }

    protected function lpaHasCorrespondent()
    {
        return ($this->lpaHasApplicant() && $this->lpa->getDocument()->getCorrespondent() instanceof Correspondence);
    }

    protected function lpaHasApplicant()
    {
        return ($this->hasInstructionOrPreference() && ($this->lpa->getDocument()->getWhoIsRegistering() == 'donor'
                || (is_array($this->lpa->getDocument()->getWhoIsRegistering())
                    && count($this->lpa->getDocument()->getWhoIsRegistering()) > 0)));
    }

    /**
     * Lpa all required properties has value to qualify as an Instrument
     *
     * @return boolean
     */
    protected function lpaHasFinishedCreation()
    {
        //  For an LPA instrument to be considered complete, the LPA must...
        //  Have a Certificate provider, primary attorney(s) and
        //  instructions or preferences to not be null (they can be blank)...
        $complete = $this->lpaHasCertificateProvider()
            && $this->lpaHasPrimaryAttorney()
            && $this->hasInstructionOrPreference();

        // AND if there is > 1 Primary Attorney
        if ($this->lpaHasMultiplePrimaryAttorneys()) {
            // we need how Primary Attorney make decisions.
            $complete = $complete && $this->lpaHowPrimaryAttorneysMakeDecisionHasValue();

            // AND if we also have > 0 Replacement Attorneys...
            if ($this->lpaHasReplacementAttorney()) {
                if ($this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
                    // AND Primary Attorney are J&S...

                    // we need to know when Replacement Attorneys Step In.
                    $complete = $complete && $this->lpaWhenReplacementAttorneyStepInHasValue();

                    // If the Replacement Attorneys don't step in until all the Primary Attorney are gone...
                    if ($this->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct()) {
                        // AND we have > 1 Replacement Attorneys
                        if ($this->lpaHasMultipleReplacementAttorneys()) {
                            // We also need to know how they will make decision when they do step in.
                            $complete = $complete && $this->lpaHowReplacementAttorneysMakeDecisionHasValue();
                        }
                    }
                } elseif ($this->lpaPrimaryAttorneysMakeDecisionJointly()) {
                    // AND Primary Attorney are J...

                    // AND we have > 1 Replacement Attorneys
                    if ($this->lpaHasMultipleReplacementAttorneys()) {
                        // We need to know how Replacement Attorneys will make decisions.
                        $complete = $complete && $this->lpaHowReplacementAttorneysMakeDecisionHasValue();
                    }
                }
            }
        } elseif ($this->lpaHasMultipleReplacementAttorneys()) {
            // Else if there are > 0  Replacement Attorneys (but only 1 Primary Attorney)
            $complete = $complete && $this->lpaHowReplacementAttorneysMakeDecisionHasValue();
        }

        return $complete;
    }

    /**
     * LPA Instrument is created and created date is set
     *
     * @return boolean
     */
    protected function lpaHasCreated()
    {
        return ($this->lpaHasFinishedCreation() && $this->lpa->getCreatedAt() !== null);
    }

    protected function hasInstructionOrPreference()
    {
        return ($this->peopleToNotifySatified()
            && !is_null($this->lpa->getDocument()->getInstruction())
            && !is_null($this->lpa->getDocument()->getPreference()));
    }

    /**
     * Simple function to reflect if the people to notify question has been answered
     * IMPORTANT! - If the metadata answered flag has been set it is important to confirm again that the
     * certificate provider has been satisfied
     *
     * @return bool
     */
    private function peopleToNotifySatified()
    {
        return ((array_key_exists(Lpa::PEOPLE_TO_NOTIFY_CONFIRMED, $this->lpa->getMetadata()) && $this->certificateProviderSatisfied())
            || $this->lpaHasPeopleToNotify());
    }

    protected function lpaHasPeopleToNotify($index = null)
    {
        if ($this->certificateProviderSatisfied()) {
            $peopleToNotify = $this->lpa->getDocument()->getPeopleToNotify();

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
     * Simple function to reflect if the certitificate provider question has been answered
     * IMPORTANT! - This will returned true if the certificate provider was provided OR if the question was skipped
     *
     * @return bool
     */
    private function certificateProviderSatisfied()
    {
        return ($this->lpaHasCertificateProvider() || $this->lpaHasCertificateProviderSkipped());
    }

    protected function lpaHasCertificateProvider()
    {
        return ($this->primaryAttorneysAndDecisionsSatisfied()
            && $this->replacementAttorneysAndDecisionsSatisfied()
            && $this->lpa->getDocument()->getCertificateProvider() instanceof CertificateProvider);
    }

    protected function lpaHasCertificateProviderSkipped()
    {
        return ($this->primaryAttorneysAndDecisionsSatisfied()
            && $this->replacementAttorneysAndDecisionsSatisfied()
            && array_key_exists(Lpa::CERTIFICATE_PROVIDER_SKIPPED, $this->lpa->getMetadata()));
    }

    /**
     * Simple function to reflect if the replacement attorney(s) have been selected with their decisions
     * TODO - This could be more widely used in this class to simplify/refactor logic elsewhere
     *
     * @return bool
     */
    private function replacementAttorneysAndDecisionsSatisfied()
    {
        if ($this->lpaHasReplacementAttorney()) {
            if ($this->whenReplacementAttorneyStepInRequired() && !$this->lpaWhenReplacementAttorneyStepInHasValue()) {
                return false;
            }

            if ($this->lpaHasMultipleReplacementAttorneys() && $this->howReplacementAttorneyMakeDecisionRequired() && !$this->lpaHowReplacementAttorneysMakeDecisionHasValue()) {
                return false;
            }
        }

        return true;
    }

    protected function lpaHowReplacementAttorneysMakeDecisionHasValue($valueToCheck = null)
    {
        if ($this->lpaHasMultipleReplacementAttorneys()) {
            $decisions = $this->lpa->getDocument()->getReplacementAttorneyDecisions();

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

    protected function lpaReplacementAttorneysMakeDecisionJointlyAndSeverally()
    {
        return $this->lpaHowReplacementAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
    }

    protected function lpaReplacementAttorneysMakeDecisionJointly()
    {
        return $this->lpaHowReplacementAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
    }

    protected function lpaReplacementAttorneysMakeDecisionDepends()
    {
        return $this->lpaHowReplacementAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
    }

    public function lpaWhenReplacementAttorneyStepInHasValue($valueToCheck = null)
    {
        if ($this->whenReplacementAttorneyStepInRequired()) {
            $decisions = $this->lpa->getDocument()->getReplacementAttorneyDecisions();

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

    public function lpaReplacementAttorneyStepInDepends()
    {
        return $this->lpaWhenReplacementAttorneyStepInHasValue(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS);
    }

    public function lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct()
    {
        return $this->lpaWhenReplacementAttorneyStepInHasValue(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);
    }

    public function lpaReplacementAttorneyStepInWhenFirstPrimaryUnableAct()
    {
        return $this->lpaWhenReplacementAttorneyStepInHasValue(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST);
    }

    /**
     * Simple function to indicate if the when replacement attorny(s) step in question needs to be asked
     * TODO - This could be more widely used in this class to simplify/refactor logic elsewhere
     *
     * @return bool
     */
    private function whenReplacementAttorneyStepInRequired()
    {
        return ($this->lpaHasReplacementAttorney()
            && $this->lpaHasMultiplePrimaryAttorneys()
            && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally());
    }

    /**
     * Simple function to indicate if the how the replacement attorny(s) make decisions question needs to be asked
     * TODO - This could be more widely used in this class to simplify/refactor logic elsewhere
     *
     * @return bool
     */
    private function howReplacementAttorneyMakeDecisionRequired()
    {
        return ($this->lpaHasMultipleReplacementAttorneys()
            && (count($this->lpa->getDocument()->getPrimaryAttorneys()) == 1
                || $this->lpaPrimaryAttorneysMakeDecisionJointly()
                || $this->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct()));
    }

    public function lpaHasMultipleReplacementAttorneys()
    {
        return ($this->lpaHasReplacementAttorney() && count($this->lpa->getDocument()->getReplacementAttorneys()) > 1);
    }

    public function lpaHasReplacementAttorney($index = null)
    {
        if ($this->primaryAttorneysAndDecisionsSatisfied()) {
            $replacementAttorneys = $this->lpa->getDocument()->getReplacementAttorneys();

            if (is_array($replacementAttorneys)) {
                if (!is_null($index)) {
                    return (isset($replacementAttorneys[$index]) && $replacementAttorneys[$index] instanceof AbstractAttorney);
                }

                return (count($replacementAttorneys) > 0);
            }
        }

        return false;
    }

    /**
     * Simple function to reflect if the primary attorney(s) have been selected with their decisions
     * TODO - This could be more widely used in this class to simplify/refactor logic elsewhere
     *
     * @return bool
     */
    private function primaryAttorneysAndDecisionsSatisfied()
    {
        if ($this->lpaHasPrimaryAttorney()) {
            if ($this->lpaHasMultiplePrimaryAttorneys()) {
                return $this->lpaHowPrimaryAttorneysMakeDecisionHasValue();
            }

            return true;
        }

        return false;
    }

    protected function lpaHowPrimaryAttorneysMakeDecisionHasValue($valueToCheck = null)
    {
        if ($this->lpaHasMultiplePrimaryAttorneys()) {
            $decisions = $this->lpa->getDocument()->getPrimaryAttorneyDecisions();

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

    public function lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally()
    {
        return $this->lpaHowPrimaryAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
    }

    public function lpaPrimaryAttorneysMakeDecisionJointly()
    {
        return $this->lpaHowPrimaryAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
    }

    public function lpaPrimaryAttorneysMakeDecisionDepends()
    {
        return $this->lpaHowPrimaryAttorneysMakeDecisionHasValue(AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
    }

    public function lpaHasMultiplePrimaryAttorneys()
    {
        return ($this->lpaHasPrimaryAttorney() && count($this->lpa->getDocument()->getPrimaryAttorneys()) > 1);
    }

    protected function lpaHasPrimaryAttorney($index = null)
    {
        if ($this->lpaHasWhenLpaStarts() || $this->lpaHasLifeSustaining()) {
            if ($index === null) {
                return (count($this->lpa->getDocument()->getPrimaryAttorneys()) > 0);
            } else {
                return (array_key_exists($index, $this->lpa->getDocument()->getPrimaryAttorneys())
                    && $this->lpa->getDocument()->getPrimaryAttorneys()[$index] instanceof AbstractAttorney);
            }
        }

        return false;
    }

    protected function lpaHasTrustCorporation($whichGroup = null)
    {
        if ($this->lpaHasWhenLpaStarts() || $this->lpaHasLifeSustaining()) {
            //  By default we will check all the attorneys
            $primaryAttorneys = $this->lpa->getDocument()->getPrimaryAttorneys();
            $replacementAttorneys = $this->lpa->getDocument()->getReplacementAttorneys();
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

    protected function lpaHasLifeSustaining()
    {
        return ($this->lpaHasDonor()
            && $this->lpa->getDocument()->getType() == Document::LPA_TYPE_HW
            && $this->lpa->getDocument()->getPrimaryAttorneyDecisions() instanceof PrimaryAttorneyDecisions
            && is_bool($this->lpa->getDocument()->getPrimaryAttorneyDecisions()->isCanSustainLife()));
    }

    protected function lpaHasWhenLpaStarts()
    {
        return ($this->lpaHasDonor()
            && $this->lpa->getDocument()->getType() == Document::LPA_TYPE_PF
            && $this->lpa->getDocument()->getPrimaryAttorneyDecisions() instanceof PrimaryAttorneyDecisions
            && in_array($this->lpa->getDocument()->getPrimaryAttorneyDecisions()->getWhen(), [
                PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
                PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW
            ]));
    }

    protected function lpaHasDonor()
    {
        return ($this->lpaHasType() && $this->lpa->getDocument()->getDonor() instanceof Donor);
    }

    protected function lpaHasType()
    {
        return ($this->lpaHasDocument() && $this->lpa->getDocument()->getType() != null);
    }

    protected function lpaHasDocument()
    {
        return ($this->lpa->getDocument() instanceof Document);
    }
}
