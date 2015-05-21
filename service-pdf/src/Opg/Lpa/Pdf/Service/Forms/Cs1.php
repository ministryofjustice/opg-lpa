<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class Cs1 extends AbstractForm
{
    use AttorneysTrait;
    
    private $actorType, $actors;
    
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
     * @param string $actorType
     */
    public function __construct(Lpa $lpa, $actorType)
    {
        parent::__construct($lpa);
        
        $this->actorType = $actorType;
        
        /**
         * One of LPA document actors property name
         * @var string - primaryAttorneys | replacementAttorneys | peopleToNotify
         */
        $actors = self::$SETTINGS['actors'][$actorType];
        
        if(($lpa->document->type == Document::LPA_TYPE_PF) && ($actorType != 'peopleToNotify')) {
            $this->actors = $this->sortAttorneys($actors);
        }
        else {
            $this->actors = $this->lpa->document->$actors;
        }
        
        sort($this->actors);
    }
    
    /**
     * Calculate how many CS1 pages need to be generated to fit all content in for a field.
     * 
     * @return array - CS1 pdf paths
     */
    public function generate()
    {
        /**
         * Number of same roles filled on standard form.
         */
        $startingIndexForThisActor = self::$SETTINGS['max-slots-on-standard-form'][$this->actorType];
        
        /**
         * Number of persons can be put on to CS1
         */
        $maxNumPersonOnCS1 = self::$SETTINGS['max-slots-on-cs1-form'];
        
        $total = count($this->actors);
        
        $totalAdditionalPersonsOfThisActorType = $total - $startingIndexForThisActor;
        
        $noOfAdditionalPages = ceil($totalAdditionalPersonsOfThisActorType/2);
        
        $totalPopulatedAdditionalPeople = 0;
        
        for($i=0; $i<$noOfAdditionalPages; $i++) {
            
            $filePath = $this->registerTempFile('CS1');
            
            $cs1 = PdfProcessor::getPdftkInstance($this->pdfTemplatePath."/LPC_Continuation_Sheet_1.pdf");
            
            $formData = array();
            for($j=0; $j<$maxNumPersonOnCS1; $j++) {
                
                $actorIndex = $i*$maxNumPersonOnCS1 + $j + $startingIndexForThisActor;
                
                $formData['cs1-'.$j.'-is-'.$this->actorType] = self::CHECK_BOX_ON;
                if(is_object($this->actors[$actorIndex]->name)) {
                    $formData['cs1-'.$j.'-name-title']       = $this->actors[$actorIndex]->name->title;
                    $formData['cs1-'.$j.'-name-first']       = $this->actors[$actorIndex]->name->first;
                    $formData['cs1-'.$j.'-name-last']        = $this->actors[$actorIndex]->name->last;
                }
                else {
                    $formData['cs1-'.$j.'-name-last']        = $this->actors[$actorIndex]->name;
                }
                
                $formData['cs1-'.$j.'-address-address1'] = $this->actors[$actorIndex]->address->address1;
                $formData['cs1-'.$j.'-address-address2'] = $this->actors[$actorIndex]->address->address2;
                $formData['cs1-'.$j.'-address-address3'] = $this->actors[$actorIndex]->address->address3;
                $formData['cs1-'.$j.'-address-postcode']  = $this->actors[$actorIndex]->address->postcode;
                
                if(property_exists($this->actors[$actorIndex], 'dob') && is_object($this->actors[$actorIndex]->dob) && property_exists($this->actors[$actorIndex]->dob, 'date')) {
                    $formData['cs1-'.$j.'-dob-date-day']   = $this->actors[$actorIndex]->dob->date->format('d');
                    $formData['cs1-'.$j.'-dob-date-month'] = $this->actors[$actorIndex]->dob->date->format('m');
                    $formData['cs1-'.$j.'-dob-date-year']  = $this->actors[$actorIndex]->dob->date->format('Y');
                }
                
                if(property_exists($this->actors[$actorIndex], 'email') && is_object($this->actors[$actorIndex]->email) && property_exists($this->actors[$actorIndex]->email, 'address')) {
                    $formData['cs1-'.$j.'-email-address']  = $this->actors[$actorIndex]->email->address;
                }
                
                if(++$totalPopulatedAdditionalPeople >= $totalAdditionalPersonsOfThisActorType) {
                    break;
                }
                
            } // end for loop for 2 persons per page
            
            $formData['donor-full-name'] = $this->fullName($this->lpa->document->donor->name);
            
            $cs1->fillForm($formData)
                ->flatten()
                ->saveAs($filePath);
            
        } // loop each CS page
        
        
        // draw cross lines if there's any blank slot in the last CS1 pdf
        if($totalAdditionalPersonsOfThisActorType % self::$SETTINGS['max-slots-on-cs1-form']) {
            $this->drawCrossLines($filePath, array(array('cs1')));
        }
        
        return $this->interFileStack;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class Cs1