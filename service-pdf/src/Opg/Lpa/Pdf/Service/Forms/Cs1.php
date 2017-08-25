<?php

namespace Opg\Lpa\Pdf\Service\Forms;

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
     * Variable to store CS1 PDF documents as they're generated
     *
     * @var array
     */
    private $cs1Pdfs = [];

    /**
     * Get the CS1 arrays
     *
     * @return array
     */
    public function getCs1s()
    {
        return $this->cs1Pdfs;
    }

    /**
     * Calculate how many CS1 pages need to be generated to fit all content in for a field.
     *
     * @return array - CS1 pdf paths
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $formData = [];

        //  Loop through these actor types and write any "additional" ones to this continuation sheet PDF
        $additionalActorTypes = [
            'primaryAttorneys'     => self::MAX_ATTORNEYS_ON_STANDARD_FORM,
            'replacementAttorneys' => self::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM,
            'peopleToNotify'       => self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM,
        ];

        $pageNumber = 0;
        $startNewPage = true;

        foreach ($additionalActorTypes as $additionalActorType => $normalActorMax) {
            //  Try to get any additional actors
            $actors = $this->lpa->document->$additionalActorType;

            if (count($actors) > $normalActorMax) {
                //  If we are dealing with attorneys then re-get the actors ordered
                if (strpos($additionalActorType, 'Attorneys') > 0) {
                    $actors = $this->sortAttorneys($additionalActorType);

                    //  Also trim the 's' off the end of the actor type string so it can be used to set the checkbox value
                    $additionalActorType = substr($additionalActorType, 0, -1);
                }

                foreach (array_splice($actors, $normalActorMax) as $additionalActor) {
                    $positionIdx = 1;

                    if ($startNewPage) {
                        $pageNumber++;
                        $positionIdx = 0;

                        //  Set up the data storage array
                        $formData[$pageNumber] = [];
                        $formDataForPage = &$formData[$pageNumber];

                        //  If this is a new page then add the common data
                        $formDataForPage['cs1-donor-full-name'] = $this->lpa->document->donor->name->__toString();
                        $formDataForPage['cs1-footer-right'] = $this->config['footer']['cs1'];
                    }

                    $formDataForPage['cs1-' . $positionIdx . '-is'] = $additionalActorType;

                    if ($additionalActor->name instanceof Name) {
                        $formDataForPage['cs1-' . $positionIdx . '-name-title'] = $additionalActor->name->title;
                        $formDataForPage['cs1-' . $positionIdx . '-name-first'] = $additionalActor->name->first;
                        $formDataForPage['cs1-' . $positionIdx . '-name-last'] = $additionalActor->name->last;
                    }

                    $formDataForPage['cs1-' . $positionIdx . '-address-address1'] = $additionalActor->address->address1;
                    $formDataForPage['cs1-' . $positionIdx . '-address-address2'] = $additionalActor->address->address2;
                    $formDataForPage['cs1-' . $positionIdx . '-address-address3'] = $additionalActor->address->address3;
                    $formDataForPage['cs1-' . $positionIdx . '-address-postcode'] = $additionalActor->address->postcode;

                    if (property_exists($additionalActor, 'dob')) {
                        $formDataForPage['cs1-' . $positionIdx . '-dob-date-day'] = $additionalActor->dob->date->format('d');
                        $formDataForPage['cs1-' . $positionIdx . '-dob-date-month'] = $additionalActor->dob->date->format('m');
                        $formDataForPage['cs1-' . $positionIdx . '-dob-date-year'] = $additionalActor->dob->date->format('Y');
                    }

                    if (property_exists($additionalActor, 'email') && ($additionalActor->email instanceof EmailAddress)) {
                        $formDataForPage['cs1-' . $positionIdx . '-email-address'] = "\n" . $additionalActor->email->address;
                    }

                    //  Toggle the start new page flag
                    $startNewPage = !$startNewPage;
                }
            }
        }

        //  Loop through the data and create the pages and add a strike through line if required
        foreach ($formData as $thisPageNumber => $thisPageData) {
            $filePath = $this->registerTempFile('CS1');

            $this->cs1Pdfs[] = $pdf = $this->getPdfObject(true);
            $pdf->fillForm($thisPageData)
                ->flatten()
                ->saveAs($filePath);

            //  If required draw a strike through line
            if ($pageNumber == $thisPageNumber && !$startNewPage) {
                $this->addStrikeThrough('cs1');
                $this->drawStrikeThroughs($filePath);
            }
        }

        return $this->interFileStack;
    }
}
