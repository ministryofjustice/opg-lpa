<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

/**
 * Class Lpa120
 * @package Opg\Lpa\Pdf
 */
class Lpa120 extends AbstractIndividualPdf
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var string
     */
    protected string $templateFileName = 'LPA120.pdf';

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to
     * the file system
     *
     * @param Lpa $lpa
     *
     * @return void
     */
    protected function create(Lpa $lpa): void
    {
        // No content on pages 1 & 2
        $this->populatePageThree($lpa);
        $this->populatePageFour($lpa->getPayment());
    }

    /**
     * @param Lpa $lpa
     *
     * @return void
     */
    private function populatePageThree(Lpa $lpa): void
    {
        $lpaDocument = $lpa->getDocument();
        $lpaRepeatCaseNumber = $lpa->getRepeatCaseNumber();
        $lpaDonor = $lpaDocument->getDonor();

        // Set the donor details
        $this->setData('donor-full-name', (string) $lpaDonor->getName())
             ->setData('donor-address', (string) $lpaDonor->getAddress(), true);

        // Set repeat case details
        if (!is_null($lpaRepeatCaseNumber)) {
            $this->setCheckBox('is-repeat-application')
                ->setData('case-number', $lpaRepeatCaseNumber);
        }

        $this->setData(
            'lpa-type',
            $lpaDocument->getType() == Document::LPA_TYPE_PF ?
            'property-and-financial-affairs' : 'health-and-welfare'
        );

        // IMPORTANT NOTE!
        // The details to be entered as the "applicant" below should ALWAYS be to correspondent details and
        // NOT the applicant as we understand it in the data
        $correspondent = $lpaDocument->getCorrespondent();
        $correspondentName = $correspondent->getName();
        $correspondentType = $correspondent->getWho();
        $correspondentTypeOther = '';

        // Set the type
        if (!in_array($correspondentType, [Correspondence::WHO_DONOR, Correspondence::WHO_ATTORNEY])) {
            $correspondentType = 'other';
            $correspondentTypeOther = 'Correspondent';
        }

        $this->setData('applicant-type', $correspondentType);
        $this->setData('applicant-type-other', $correspondentTypeOther);

        // Set the name
        if ($correspondentName instanceof Name || $correspondentName instanceof LongName) {
            $correspondentTitle = $correspondentName->getTitle();
            $correspondentTitleOther = '';

            if (!in_array($correspondentTitle, ['Mr', 'Mrs', 'Miss', 'Ms'])) {
                $correspondentTitleOther = $correspondentTitle;
                $correspondentTitle = 'other';
            }

            $this->setData('applicant-name-title', strtolower($correspondentTitle))
                ->setData('applicant-name-title-other', $correspondentTitleOther)
                ->setData('applicant-name-first', $correspondentName->getFirst())
                ->setData('applicant-name-last', $correspondentName->getLast());
        }

        // Set the address
        if ($correspondent->getAddress() instanceof Address) {
            $this->setData('applicant-address', (string) $correspondent->getAddress(), true);
        }

        // Set the phone number
        if ($correspondent->getPhone() instanceof PhoneNumber) {
            $this->setData('applicant-phone-number', $correspondent->getPhone()->getNumber());
        }

        // Set the email address
        if ($correspondent->getEmail() instanceof EmailAddress) {
            $this->setData('applicant-email-address', (string) $correspondent->getEmail());
        }
    }

    /**
     * @param Payment $payment
     */
    private function populatePageFour(Payment $payment): void
    {
        $this->setData(
            'receive-benefits',
            $this->getYesNoEmptyValueFromBoolean($payment->isReducedFeeReceivesBenefits())
        )
            ->setData('damage-awarded', empty($payment->isReducedFeeAwardedDamages()) ?
                '' : $this->getYesNoEmptyValueFromBoolean(!$payment->isReducedFeeAwardedDamages()))
            ->setData('low-income', $this->getYesNoEmptyValueFromBoolean($payment->isReducedFeeLowIncome()))
            ->setData(
                'receive-universal-credit',
                $this->getYesNoEmptyValueFromBoolean($payment->isReducedFeeUniversalCredit())
            );
    }

    /**
     * Simple function to return a yes/no string or empty value
     *
     * @param bool $valueIn
     *
     * @return string
     */
    private function getYesNoEmptyValueFromBoolean(bool $valueIn): string
    {
        if ($valueIn === true) {
            return 'yes';
        } elseif ($valueIn === false) {
            return 'no';
        }

        return '';
    }
}
