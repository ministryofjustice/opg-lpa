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
     * @var
     */
    protected $templateFileName = 'LPA120.pdf';

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        //  No content on pages 1 & 2
        $this->populatePageThree($lpa);
        $this->populatePageFour($lpa->payment);
    }

    /**
     * @param Lpa $lpa
     */
    private function populatePageThree(Lpa $lpa)
    {
        //  Set the donor details
        $this->setData('donor-full-name', (string) $lpa->document->donor->name)
             ->setData('donor-address', (string) $lpa->document->donor->address, true);

        //  Set repeat case details
        if (!is_null($lpa->repeatCaseNumber)) {
            $this->setCheckBox('is-repeat-application')
                 ->setData('case-number', $lpa->repeatCaseNumber);
        }

        $this->setData('lpa-type', $lpa->document->type == Document::LPA_TYPE_PF ? 'property-and-financial-affairs' : 'health-and-welfare');

        //  IMPORTANT NOTE!
        //  The details to be entered as the "applicant" below should ALWAYS be to correspondent details and NOT the applicant as we understand it in the data
        $correspondent = $lpa->document->correspondent;
        $correspondentType = $correspondent->who;
        $correspondentTypeOther = '';

        //  Set the type
        if (!in_array($correspondentType, [Correspondence::WHO_DONOR, Correspondence::WHO_ATTORNEY])) {
            $correspondentType = 'other';
            $correspondentTypeOther = 'Correspondent';
        }

        $this->setData('applicant-type', $correspondentType);
        $this->setData('applicant-type-other', $correspondentTypeOther);

        //  Set the name
        if ($correspondent->name instanceof Name || $correspondent->name instanceof LongName) {
            $correspondentTitle = $correspondent->name->title;
            $correspondentTitleOther = '';

            if (!in_array($correspondentTitle, ['Mr', 'Mrs', 'Miss', 'Ms'])) {
                $correspondentTitleOther = $correspondentTitle;
                $correspondentTitle = 'other';
            }

            $this->setData('applicant-name-title', strtolower($correspondentTitle))
                 ->setData('applicant-name-title-other', $correspondentTitleOther)
                 ->setData('applicant-name-first', $correspondent->name->first)
                 ->setData('applicant-name-last', $correspondent->name->last);
        }

        //  Set the address
        if ($correspondent->address instanceof Address) {
            $this->setData('applicant-address', (string) $correspondent->address, true);
        }

        //  Set the phone number
        if ($correspondent->phone instanceof PhoneNumber) {
            $this->setData('applicant-phone-number', $correspondent->phone->number);
        }

        //  Set the email address
        if ($correspondent->email instanceof EmailAddress) {
            $this->setData('applicant-email-address', (string) $correspondent->email);
        }
    }

    /**
     * @param Payment $payment
     */
    private function populatePageFour(Payment $payment)
    {
        $this->setData('receive-benefits', $this->getYesNoEmptyValueFromBoolean($payment->reducedFeeReceivesBenefits))
             ->setData('damage-awarded', empty($payment->reducedFeeAwardedDamages) ? '' : $this->getYesNoEmptyValueFromBoolean(!$payment->reducedFeeAwardedDamages))
             ->setData('low-income', $this->getYesNoEmptyValueFromBoolean($payment->reducedFeeLowIncome))
             ->setData('receive-universal-credit', $this->getYesNoEmptyValueFromBoolean($payment->reducedFeeUniversalCredit));
    }

    /**
     * Simple function to return a yes/no string or empty value
     *
     * @param $valueIn
     * @return null|string
     */
    private function getYesNoEmptyValueFromBoolean($valueIn)
    {
        if ($valueIn === true) {
            return 'yes';
        } elseif ($valueIn === false) {
            return 'no';
        }

        return '';
    }
}
