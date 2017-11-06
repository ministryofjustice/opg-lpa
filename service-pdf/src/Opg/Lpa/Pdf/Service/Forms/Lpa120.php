<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use Exception;
use RuntimeException;

class Lpa120 extends AbstractTopForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile = 'LPA120.pdf';

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        $stateChecker = new StateChecker($lpa);

        //  Check that the document can be created
        if (!$stateChecker->canGenerateLPA120()) {
            throw new RuntimeException('LPA does not contain all the required data to generate a LPA120');
        }
    }

    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     *
     * @return $this
     * @throws Exception
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $this->generatedPdfFilePath = $this->getTmpFilePath();

        $lpa = $this->lpa;
        $lpaDocument = $lpa->document;
        $lpaPayment = $lpa->payment;

        $applicantType = 'other';
        $applicantTypeOther = null;
        $applicantPhoneNumber = null;

        //  The correspondent takes precedence over who is registering if specified
        if ($lpaDocument->correspondent instanceof Correspondence) {
            $applicant = $lpaDocument->correspondent;

            if ($applicant->who == Correspondence::WHO_DONOR) {
                $applicantType = 'donor';
            } elseif ($applicant->who == Correspondence::WHO_ATTORNEY) {
                $applicantType = 'attorney';
            } else {
                $applicantTypeOther = 'Correspondent';
            }

            if ($applicant->phone instanceof PhoneNumber) {
                //  If the correspondent has a phone number then grab that value now
                $applicantPhoneNumber = $applicant->phone->number;
            }
        } else {
            //  Get applicant object
            if ($lpaDocument->whoIsRegistering == 'donor') {
                $applicant = $lpaDocument->donor;
                $applicantType = 'donor';
            } elseif (is_array($lpaDocument->whoIsRegistering)) {
                //  Get the first element in the whoIsRegistering array as the attorney applicant of the LPA
                foreach ($lpaDocument->whoIsRegistering as $attorneyId) {
                    $applicant = $lpaDocument->getPrimaryAttorneyById($attorneyId);
                    $applicantType = 'attorney';
                    break;
                }
            } else {
                throw new Exception('When generating LPA120, applicant was found invalid');
            }
        }

        //  Get the applicant name details
        $applicantTitle = null;
        $applicantTitleOther = null;
        $applicantFirstName = null;
        $applicantLastName = $applicant->name;  //  Default the applicant last name here in case the value is a string for a company

        if ($applicant->name instanceof Name || $applicant->name instanceof LongName) {
            $applicantTitle = strtolower($applicant->name->title);

            //  If the applicant title is an other type then swap the values around
            if (!in_array($applicantTitle, ['mr', 'mrs', 'miss', 'ms'])) {
                $applicantTitleOther = $applicant->name->title; //  Use the original value here and not the lowercase version
                $applicantTitle = 'other';
            }

            $applicantFirstName = $applicant->name->first;
            $applicantLastName = $applicant->name->last;
        }

        $formData = [];

        $formData['donor-full-name'] = $lpaDocument->donor->name->__toString();
        $formData['donor-address'] = "\n" . (string)$lpaDocument->donor->address;
        $formData['lpa-type'] = ($lpaDocument->type == Document::LPA_TYPE_PF ? 'property-and-financial-affairs' : 'health-and-welfare');
        $formData['is-repeat-application'] = (is_null($lpa->repeatCaseNumber) ? null : self::CHECK_BOX_ON);
        $formData['case-number'] = $lpa->repeatCaseNumber;
        $formData['applicant-type'] = $applicantType;
        $formData['applicant-type-other'] = $applicantTypeOther;
        $formData['applicant-name-title'] = $applicantTitle;
        $formData['applicant-name-title-other'] = $applicantTitleOther;
        $formData['applicant-name-first'] = $applicantFirstName;
        $formData['applicant-name-last'] = $applicantLastName;
        $formData['applicant-address'] = "\n" . ($applicant->address instanceof Address ? (string)$applicant->address : '');
        $formData['applicant-phone-number'] = $applicantPhoneNumber;
        $formData['applicant-email-address'] = ($applicant->email instanceof EmailAddress ? (string)$applicant->email : null);
        $formData['receive-benefits'] = $this->getYesNoNullValueFromBoolean($lpaPayment->reducedFeeReceivesBenefits);
        $formData['damage-awarded'] = (is_null($lpaPayment->reducedFeeAwardedDamages) ? null : $this->getYesNoNullValueFromBoolean(!$lpaPayment->reducedFeeAwardedDamages));
        $formData['low-income'] = $this->getYesNoNullValueFromBoolean($lpaPayment->reducedFeeLowIncome);
        $formData['receive-universal-credit'] = $this->getYesNoNullValueFromBoolean($lpaPayment->reducedFeeUniversalCredit);

        // populate forms
        $pdf = $this->getPdfObject();
        $pdf->fillForm($formData)
            ->flatten()
            ->saveAs($this->generatedPdfFilePath);

        $this->protectPdf();

        return $this;
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
