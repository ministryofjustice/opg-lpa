<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

/**
 * Class Lpa120
 * @package Opg\Lpa\Pdf
 */
class Lpa120 extends AbstractPdf
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LPA120.pdf';

    /**
     * @var array
     */
    protected $leadingNewLineFields = [
        'applicant-address',
        'donor-address'
    ];

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        $this->setData('lpa-type', $lpa->document->type == Document::LPA_TYPE_PF ? 'property-and-financial-affairs' : 'health-and-welfare');

        $this->setDonorData($lpa->document->donor);

        $this->setPaymentDetails($lpa->payment);

        //  Set repeat case details
        if (!is_null($lpa->repeatCaseNumber)) {
            $this->setData('is-repeat-application', self::CHECK_BOX_ON)
                 ->setData('case-number', $lpa->repeatCaseNumber);
        }

        //  IMPORTANT NOTE!
        //  The details to be entered as the "applicant" below should ALWAYS be to correspondent details and NOT the applicant as we understand it in the data
        $correspondent = $lpa->document->correspondent;
        $correspondentType = $correspondent->who;

        //  Set the type
        if (!in_array($correspondentType, [Correspondence::WHO_DONOR, Correspondence::WHO_ATTORNEY])) {
            $correspondentType = 'other';
            $this->setData('applicant-type-other', 'Correspondent');
        }

        $this->setData('applicant-type', $correspondentType);

        //  Set the name
        if ($correspondent->name instanceof Name || $correspondent->name instanceof LongName) {
            $correspondentTitle = $correspondent->name->title;

            if (!in_array($correspondentTitle, ['Mr', 'Mrs', 'Miss', 'Ms'])) {
                $correspondentTitle = 'other';
                $this->setData('applicant-name-title-other', $correspondentTitle);
            }

            $this->setData('applicant-name-title', strtolower($correspondentTitle))
                 ->setData('applicant-name-first', $correspondent->name->first)
                 ->setData('applicant-name-last', $correspondent->name->last);
        }

        //  Set the phone number
        if ($correspondent->phone instanceof PhoneNumber) {
            $this->setData('applicant-phone-number', $correspondent->phone->number);
        }

        //  Set the address
        if ($correspondent->address instanceof Address) {
            $this->setData('applicant-address', (string) $correspondent->address);
        }

        //  Set the email address
        if ($correspondent->email instanceof EmailAddress) {
            $this->setData('applicant-email-address', (string) $correspondent->email);
        }
    }

    /**
     * @param Donor $donor
     */
    private function setDonorData(Donor $donor)
    {
        $this->setData('donor-full-name', (string) $donor->name)
             ->setData('donor-address', (string) $donor->address);
    }

    /**
     * @param Payment $payment
     */
    private function setPaymentDetails(Payment $payment)
    {
        $this->setData('receive-benefits', $this->getYesNoNullValueFromBoolean($payment->reducedFeeReceivesBenefits))
             ->setData('damage-awarded', is_null($payment->reducedFeeAwardedDamages) ? null : $this->getYesNoNullValueFromBoolean(!$payment->reducedFeeAwardedDamages))
             ->setData('low-income', $this->getYesNoNullValueFromBoolean($payment->reducedFeeLowIncome))
             ->setData('receive-universal-credit', $this->getYesNoNullValueFromBoolean($payment->reducedFeeUniversalCredit));
    }

    /**
     * Simple function to return a yes/no string or null value
     *
     * @param $valueIn
     * @return null|string
     */
    private function getYesNoNullValueFromBoolean($valueIn)
    {
        if ($valueIn === true) {
            return 'yes';
        } elseif ($valueIn === false) {
            return 'no';
        }

        return null;
    }
}
