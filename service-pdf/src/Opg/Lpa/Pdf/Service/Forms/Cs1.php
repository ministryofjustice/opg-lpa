<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\Name;

class Cs1 extends AbstractForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile = 'LPC_Continuation_Sheet_1.pdf';

    /**
     * Calculate how many CS1 pages need to be generated to fit all content in for a field.
     *
     * @return array - CS1 pdf paths
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $settings = array(
            'max-slots-on-standard-form' => [
                'primaryAttorney' => self::MAX_ATTORNEYS_ON_STANDARD_FORM,
                'replacementAttorney' => self::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM,
                'peopleToNotify' => self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM
            ],
            'max-slots-on-cs1-form' => 2,
            'actors' => [
                'primaryAttorney' => 'primaryAttorneys',
                'replacementAttorney' => 'replacementAttorneys',
                'peopleToNotify' => 'peopleToNotify'
            ]
        );

        $actorTypes = [];

        // CS1 is to be generated when number of attorneys that are larger than what is available on standard form
        if (count($this->lpa->document->primaryAttorneys) > self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            $actorTypes[] = 'primaryAttorney';
        }

        if (count($this->lpa->document->replacementAttorneys) > self::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM) {
            $actorTypes[] = 'replacementAttorney';
        }

        // CS1 is to be generated when number of people to notify are larger than what is available on standard form
        if (count($this->lpa->document->peopleToNotify) > self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            $actorTypes[] = 'peopleToNotify';
        }

        $actors = [];

        //  One of LPA document actors property name
        //  @var string - primaryAttorneys | replacementAttorneys | peopleToNotify
        foreach ($actorTypes as $actorType) {
            $actorGroup = $settings['actors'][$actorType];

            if ($this->lpa->document->type == Document::LPA_TYPE_PF && $actorGroup != 'peopleToNotify') {
                $actorsByType = $this->sortAttorneys($actorGroup);
            } else {
                $actorsByType = $this->lpa->document->$actorGroup;
            }

            $actors[$actorType] = $actorsByType;
        }

        $additionalActors = [];

        foreach ($actors as $actorType => $actorGroup) {
            $startingIndexForThisActorGroup = $settings['max-slots-on-standard-form'][$actorType];
            $totalActorsInGroup = count($actorGroup);
            for ($i = $startingIndexForThisActorGroup; $i < $totalActorsInGroup; $i++) {
                $additionalActors[] = ['person' => $actorGroup[$i], 'type' => $actorType];
            }
        }

        foreach ($additionalActors as $idx => $actor) {
            $pIdx = ($idx % $settings['max-slots-on-cs1-form']);

            if ($pIdx == 0) {
                //  Initialise the dataForForm to empty it
                $this->dataForForm = [];

                $this->dataForForm['cs1-donor-full-name'] = $this->lpa->document->donor->name->__toString();
                $this->dataForForm['cs1-footer-right'] = $this->config['footer']['cs1'];
            }

            $this->dataForForm['cs1-' . $pIdx . '-is'] = $actor['type'];

            if ($actor['person']->name instanceof Name) {
                $this->dataForForm['cs1-' . $pIdx . '-name-title'] = $actor['person']->name->title;
                $this->dataForForm['cs1-' . $pIdx . '-name-first'] = $actor['person']->name->first;
                $this->dataForForm['cs1-' . $pIdx . '-name-last'] = $actor['person']->name->last;
            }

            $this->dataForForm['cs1-' . $pIdx . '-address-address1'] = $actor['person']->address->address1;
            $this->dataForForm['cs1-' . $pIdx . '-address-address2'] = $actor['person']->address->address2;
            $this->dataForForm['cs1-' . $pIdx . '-address-address3'] = $actor['person']->address->address3;
            $this->dataForForm['cs1-' . $pIdx . '-address-postcode'] = $actor['person']->address->postcode;

            if (property_exists($actor['person'], 'dob')) {
                $this->dataForForm['cs1-' . $pIdx . '-dob-date-day'] = $actor['person']->dob->date->format('d');
                $this->dataForForm['cs1-' . $pIdx . '-dob-date-month'] = $actor['person']->dob->date->format('m');
                $this->dataForForm['cs1-' . $pIdx . '-dob-date-year'] = $actor['person']->dob->date->format('Y');
            }

            if (property_exists($actor['person'], 'email') && ($actor['person']->email instanceof EmailAddress)) {
                $this->dataForForm['cs1-' . $pIdx . '-email-address'] = "\n" . $actor['person']->email->address;
            }

            if ($pIdx == 1) {
                $filePath = $this->registerTempFile('CS1');
                $pdf = $this->getPdfObject(true);
                $pdf->fillForm($this->dataForForm)
                    ->flatten()
                    ->saveAs($filePath);
            }
        }

        if ($pIdx == 0) {
            $filePath = $this->registerTempFile('CS1');

            $pdf = $this->getPdfObject(true);
            $pdf->fillForm($this->dataForForm)
                ->flatten()
                ->saveAs($filePath);

            // draw cross lines if there's any blank slot in the last CS1 pdf
            $this->drawCrossLines($filePath, array(array('cs1')));
        }

        return $this->interFileStack;
    }
}
