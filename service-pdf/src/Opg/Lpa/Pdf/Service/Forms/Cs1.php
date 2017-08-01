<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\PdftkInstance;

class Cs1 extends AbstractForm
{
    use AttorneysTrait;

    private $actorTypes, $actors=[];

    static $SETTINGS = array(
        'max-slots-on-standard-form' => [
            'primaryAttorney'       => Lp1::MAX_ATTORNEYS_ON_STANDARD_FORM,
            'replacementAttorney'   => Lp1::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM,
            'peopleToNotify'        => Lp1::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM
        ],
        'max-slots-on-cs1-form'     => 2,
        'actors'                 => [
            'primaryAttorney'       => 'primaryAttorneys',
            'replacementAttorney'   => 'replacementAttorneys',
            'peopleToNotify'        => 'peopleToNotify'
        ]
    );

    /**
     *
     * @param Lpa $lpa
     * @param array $actorTypes
     */
    public function __construct(Lpa $lpa, $actorTypes)
    {
        parent::__construct($lpa);

        $this->actorTypes = $actorTypes;

        /**
         * One of LPA document actors property name
         * @var string - primaryAttorneys | replacementAttorneys | peopleToNotify
         */
        foreach($actorTypes as $actorType) {
            $actorGroup = self::$SETTINGS['actors'][$actorType];

            if(($lpa->document->type == Document::LPA_TYPE_PF) && ($actorGroup != 'peopleToNotify')) {
                $actors = $this->sortAttorneys($actorGroup);
            }
            else {
                $actors = $this->lpa->document->$actorGroup;
            }

            sort($actors);

            $this->actors[$actorType] = $actors;
        }
    }

    /**
     * Calculate how many CS1 pages need to be generated to fit all content in for a field.
     *
     * @return array - CS1 pdf paths
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $additionalActors = [];

        foreach($this->actors as $actorType => $actorGroup) {
            $startingIndexForThisActorGroup = self::$SETTINGS['max-slots-on-standard-form'][$actorType];
            $totalActorsInGroup = count($actorGroup);
            for($i=$startingIndexForThisActorGroup; $i<$totalActorsInGroup; $i++) {
                $additionalActors[] = ['person'=>$actorGroup[$i], 'type'=>$actorType];
            }
        }

        if(empty($additionalActors)) return;

        foreach($additionalActors as $idx=>$actor) {
            $pIdx = ($idx % self::$SETTINGS['max-slots-on-cs1-form']);

            if($pIdx == 0) {
                $this->pdfFormData['cs1-donor-full-name'] = $this->fullName($this->lpa->document->donor->name);
                $this->pdfFormData['cs1-footer-right'] = Config::getInstance()['footer']['cs1'];
            }

            $this->pdfFormData['cs1-'.$pIdx.'-is'] = $actor['type'];

            if($actor['person']->name instanceof Name) {
                $this->pdfFormData['cs1-'.$pIdx.'-name-title'] = $actor['person']->name->title;
                $this->pdfFormData['cs1-'.$pIdx.'-name-first'] = $actor['person']->name->first;
                $this->pdfFormData['cs1-'.$pIdx.'-name-last']  = $actor['person']->name->last;
            }

            $this->pdfFormData['cs1-'.$pIdx.'-address-address1'] = $actor['person']->address->address1;
            $this->pdfFormData['cs1-'.$pIdx.'-address-address2'] = $actor['person']->address->address2;
            $this->pdfFormData['cs1-'.$pIdx.'-address-address3'] = $actor['person']->address->address3;
            $this->pdfFormData['cs1-'.$pIdx.'-address-postcode'] = $actor['person']->address->postcode;

            if(property_exists($actor['person'], 'dob')) {
                $this->pdfFormData['cs1-'.$pIdx.'-dob-date-day']   = $actor['person']->dob->date->format('d');
                $this->pdfFormData['cs1-'.$pIdx.'-dob-date-month'] = $actor['person']->dob->date->format('m');
                $this->pdfFormData['cs1-'.$pIdx.'-dob-date-year']  = $actor['person']->dob->date->format('Y');
            }

            if(property_exists($actor['person'], 'email') && ($actor['person']->email instanceof EmailAddress)) {
                $this->pdfFormData['cs1-'.$pIdx.'-email-address'] = "\n".$actor['person']->email->address;
            }

            if($pIdx == 1) {
                $filePath = $this->registerTempFile('CS1');
                $cs1 = PdftkInstance::getInstance($this->pdfTemplatePath."/LPC_Continuation_Sheet_1.pdf");

                $cs1->fillForm($this->pdfFormData)
                    ->flatten()
                    ->saveAs($filePath);
            }
        }

        if($pIdx == 0) {
            $filePath = $this->registerTempFile('CS1');
            $cs1 = PdftkInstance::getInstance($this->pdfTemplatePath."/LPC_Continuation_Sheet_1.pdf");

            $cs1->fillForm($this->pdfFormData)
                ->flatten()
                ->saveAs($filePath);

            // draw cross lines if there's any blank slot in the last CS1 pdf
            $this->drawCrossLines($filePath, array(array('cs1')));
        }

        return $this->interFileStack;
    } // function generate()

    public function __destruct()
    {
    }
}