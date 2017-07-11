<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\Pdf\Service\PdftkInstance;

class Lpa120 extends AbstractForm
{
    private $basePdfTemplate;

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        //  Generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LPA120');

        $this->basePdfTemplate = $this->pdfTemplatePath . '/LPA120.pdf';
    }

    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     *
     * @return $this
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $lpa = $this->lpa;
        $lpaPayment = $lpa->payment;

        //  Check eligibility for exemption or remission.
        if(!$lpa->repeatCaseNumber
            && !$lpaPayment->reducedFeeLowIncome
            && !($lpaPayment->reducedFeeReceivesBenefits && $lpaPayment->reducedFeeAwardedDamages)
            && !$lpaPayment->reducedFeeUniversalCredit) {

            throw new \RuntimeException("LPA120 is not available for this LPA.");
        }

        $pdf = PdftkInstance::getInstance($this->basePdfTemplate);

        $this->generatedPdfFilePath = $this->registerTempFile('LPA120');

        // populate forms
        $mappings = $this->dataMapping();

        $pdf->fillForm($mappings)
            ->flatten()
            ->saveAs($this->generatedPdfFilePath);

        $this->protectPdf();

        return $this;
    }

    /**
     * Get the data mapping for this document
     *
     * @return array
     * @throws \Exception
     */
    protected function dataMapping()
    {
        $lpa = $this->lpa;
        $lpaDocument = $lpa->document;
        $lpaPayment = $lpa->payment;

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
            throw new \Exception('When generating LAP120, applicant was found invalid');
        }

        //  Get the applicant name details
        $applicantTitle = null;
        $applicantTitleOther = null;
        $applicantFirstName = null;
        $applicantLastName = $applicant->name;  //  Default the applicant last name here in case the value is a string for a company

        if ($applicant->name instanceof Name) {
            $applicantTitle = strtolower($applicant->name->title);

            //  If the applicant title is an other type then swap the values around
            if (!in_array($applicantTitle, ['mr','mrs','miss','ms'])) {
                $applicantTitleOther = $applicant->name->title; //  Use the original value here and not the lowercase version
                $applicantTitle = 'other';
            }

            $applicantFirstName = $applicant->name->first;
            $applicantLastName = $applicant->name->last;
        }

        //  If the correspondent has a phone number then grab that value now
        $applicantPhoneNumber = null;

        if ($lpaDocument->correspondent instanceof Correspondence
            && $lpaDocument->correspondent->phone instanceof PhoneNumber) {

            $applicantPhoneNumber = $lpaDocument->correspondent->phone->number;
        }

        $mappings = array(
            'donor-full-name'            => $this->fullName($lpaDocument->donor->name),
            'donor-address'              => "\n" . (string) $lpaDocument->donor->address,
            'lpa-type'                   => ($lpaDocument->type == Document::LPA_TYPE_PF ? 'property-and-financial-affairs' : 'health-and-welfare'),
            'is-repeat-application'      => (is_null($lpa->repeatCaseNumber) ? null : self::CHECK_BOX_ON),
            'case-number'                => $lpa->repeatCaseNumber,
            'applicant-type'             => $applicantType,
            'applicant-name-title'       => $applicantTitle,
            'applicant-name-title-other' => $applicantTitleOther,
            'applicant-name-first'       => $applicantFirstName,
            'applicant-name-last'        => $applicantLastName,
            'applicant-address'          => "\n" . ($applicant->address instanceof Address ? (string) $applicant->address : ''),
            'applicant-phone-number'     => $applicantPhoneNumber,
            'applicant-email-address'    => ($applicant->email instanceof EmailAddress ? (string) $applicant->email : null),
            'receive-benefits'           => $this->getYesNoNullValueFromBoolean($lpaPayment->reducedFeeReceivesBenefits),
            'damage-awarded'             => (is_null($lpaPayment->reducedFeeAwardedDamages) ? null : $this->getYesNoNullValueFromBoolean(!$lpaPayment->reducedFeeAwardedDamages)),
            'low-income'                 => $this->getYesNoNullValueFromBoolean($lpaPayment->reducedFeeLowIncome),
            'receive-universal-credit'   => $this->getYesNoNullValueFromBoolean($lpaPayment->reducedFeeUniversalCredit),
        );

        return $mappings;
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
        } elseif($valueIn === false) {
            return 'no';
        }

        return null;
    }
}
