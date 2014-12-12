<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Cs1 extends AbstractForm
{
    private $roleType;
    
    static $SETTINGS = array(
        'max-slots-on-standard-form' => array(
            'primaryAttorneys'      => Lp1::MAX_ATTORNEYS_ON_STANDARD_FORM,
            'replacementAttorneys'  => Lp1::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM,
            'peopleToNotify'        => Lp1::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM
        ),
        'max-slots-on-cs1-form' => 2
    );
    
    /**
     * bx - bottom x 
     * by - bottom y
     * tx - top x
     * ty - top y
     * @var array - stroke corrrdinates
     */
    protected $strokeParams = array(
        'cs1' => array('bx'=>313, 'by'=>262, 'tx'=>558, 'ty'=>645)
    );
    
    public function __construct(Lpa $lpa, $roleType)
    {
        parent::__construct($lpa);
        $this->roleType = $roleType;
    }
    
    /**
     * Calculate how many CS1 pages need to be generated to fit all content in for a field.
     * 
     * @param string $this->roleType - primaryAttorneys, replacementAttorneys or peopleToNotify.
     * @return array - CS1 pdf paths
     */
    public function generate()
    {
        /**
         * Number of same roles filled on standard form.
         */ 
        $baseIndexForThisRole = self::$SETTINGS['max-slots-on-standard-form'][$this->roleType];
        
        /**
         * Number of persons can be put on to CS1
         */
        $maxNumPersonOnCS1 = self::$SETTINGS['max-slots-on-cs1-form'];
        
        $total = count($this->lpa->document->{$this->roleType});
        $totalAdditionalPersonsOfSameRole = $total - $baseIndexForThisRole;
        
        $noOfAdditionalPages = ceil($totalAdditionalPersonsOfSameRole/2);
        
        $totalMappedAdditionalPeople = 0;
        for($i=0; $i<$noOfAdditionalPages; $i++) {
            
            $filePath = $this->registerTempFile('CS1');
            
            $cs1 = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath."/LPC_Continuation_Sheet_1.pdf");
            
            $formData = array();
            for($j=0; $j<$maxNumPersonOnCS1; $j++) {
                
                $roleIndex = $i*$maxNumPersonOnCS1 + $j + $baseIndexForThisRole;
                
                $formData['cs1-'.$j.'-is-'.$this->roleType] = self::CHECK_BOX_ON;
                $formData['cs1-'.$j.'-name-title']       = $this->flattenLpa['lpa-document-'.$this->roleType.'-'.$roleIndex.'-name-title'];
                $formData['cs1-'.$j.'-name-first']       = $this->flattenLpa['lpa-document-'.$this->roleType.'-'.$roleIndex.'-name-first'];
                $formData['cs1-'.$j.'-name-last']        = $this->flattenLpa['lpa-document-'.$this->roleType.'-'.$roleIndex.'-name-last'];
                
                $formData['cs1-'.$j.'-address-address1'] = $this->flattenLpa['lpa-document-'.$this->roleType.'-'.$roleIndex.'-address-address1'];
                $formData['cs1-'.$j.'-address-address2'] = $this->flattenLpa['lpa-document-'.$this->roleType.'-'.$roleIndex.'-address-address2'];
                $formData['cs1-'.$j.'-address-address3'] = $this->flattenLpa['lpa-document-'.$this->roleType.'-'.$roleIndex.'-address-address3'];
                $formData['cs1-'.$j.'-address-postode']  = $this->flattenLpa['lpa-document-'.$this->roleType.'-'.$roleIndex.'-address-postcode'];
                
                if(isset($this->lpa->document->{$this->roleType}[$roleIndex]->dob)) {
                    $formData['cs1-'.$j.'-dob-date-day']   = $this->lpa->document->{$this->roleType}[$roleIndex]->dob->date->format('d');
                    $formData['cs1-'.$j.'-dob-date-month'] = $this->lpa->document->{$this->roleType}[$roleIndex]->dob->date->format('m');
                    $formData['cs1-'.$j.'-dob-date-year']  = $this->lpa->document->{$this->roleType}[$roleIndex]->dob->date->format('Y');
                }
                if(isset($this->lpa->document->{$this->roleType}[$roleIndex]->email)) {
                    $formData['cs1-'.$j.'-email-address']  = $this->flattenLpa['lpa-document-'.$this->roleType.'-'.$roleIndex.'-email-address'];
                }
                
                if(++$totalMappedAdditionalPeople >= $totalAdditionalPersonsOfSameRole) {
                    break;
                }
                
            } // end for loop for 2 persons per page
            
            $formData['donor-full-name'] = $this->fullName($this->lpa->document->donor->name);
            
            $cs1->fillForm($formData)
                ->needAppearances()
                ->flatten()
                ->saveAs($filePath);
//             print_r($cs1);
            
        } // loop each CS page
        
        
        // draw strokes if there's any blank slot in the last CS1 pdf
        if($totalAdditionalPersonsOfSameRole % self::$SETTINGS['max-slots-on-cs1-form']) {
            $this->stroke($filePath, array(array('cs1')));
        }
        
        return $this->intermediateFilePaths;
    } // function addContinuationSheet()
    
    public function __destruct()
    {
        
    }
} // class Cs1