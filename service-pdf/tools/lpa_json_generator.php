<?php
use Opg\Lpa\DataModel\Lpa\Lpa;
require_once 'vendor/autoload.php';
include "Randomizer.php";

date_default_timezone_set('UTC');

class JsonGenerator extends Randomizer
{
    protected $lpa, $lpaType, $donor, $primaryAttorneys=[], $replacementAttorneys=[], $primaryAttorneyDecisions, $replacementAttorneyDecisions,
            $certificateProvider, $peopleToNotify=[], $correspondent, $applicant, $hasTrust=null;
    
    const HW = 'health-and-welfare';
    const PF = 'property-and-financial';
    static $coverage = [
            'type' => [
                    self::HW => [],
                    self::PF => []
            ],
            'canSign' => [
                    true => [],
                    false => []
            ],
            'primaryAttorney' => [
                    1 => [],
                    2 => [],
                    3 => [],
                    4 => [],
                    5 => [],
                    6 => [],
                    7 => [],
                    8 => [],
                    9 => [],
                    10=> []
            ],
            'replacementAttorney' => [
                    0 => [],
                    1 => [],
                    2 => [],
                    3 => []
            ],
            'peopleToNotify' => [
                    0 => [],
                    1 => [],
                    2 => [],
                    3 => [],
                    4 => [],
                    5 => []
            ],
            'applicant' => [
                    'donor' => [],
                    1 => [],
                    2 => [],
                    3 => [],
                    4 => [],
                    5 => [],
                    6 => [],
                    7 => [],
                    8 => []
            ],
            'correspondentIs' => [
                    'donor' => [],
                    'attorney' => [],
                    'other' => [],
            ],
            'contactByPost' => [
                    'yes' => [],
                    'no' => []
            ],
            'contactByPhone' => [
                    'yes' => [],
                    'no' => []
            ],
            'contactByEmail' => [
                    'yes' => [],
                    'no' => []
            ],
            'contactInWelsh' => [
                    'yes' => [],
                    'no' => []
            ],
            'numOfInstructionPages' => [
                    1 => [],
                    2 => [],
                    3 => []
            ],
            'numOfPreferencePages' => [
                    1 => [],
                    2 => [],
                    3 => []
            ],
            'howPrimaryAttorneyAct' => [
                    'single-attorney' => [],
                    'jointly' => [],
                    'jointly-attorney-severally' => [],
                    'depends' => [],
            ],
            'numOfAttorneyDecisionPages' => [
                    1 => [],
                    2 => [],
                    3 => []
            ],
            'whenAttorneyMakeDecision' => [
                    'now' => [],
                    'no-capacity' => []
            ],
            'life-sustaining' => [
                    'yes' => [],
                    'no' => []
            ],
            'howReplacementAttorneyAct' => [
                    null => [],
                    'single-attorney' => [],
                    'jointly' => [],
                    'jointly-attorney-severally' => [],
                    'depends' => [],
            ],
            'numOfReplacementAttorneyDecisionPages' => [
                    1 => [],
                    2 => [],
                    3 => []
            ],
            'whenReplacementStepIn' => [
                    null => [],
                    'first' => [],
                    'last' => [],
                    'depends' => [],
            ],
            'numOfReplacementStepInDecisionPages' => [
                    1 => [],
                    2 => [],
                    3 => []
            ],
            'paymentMethod' => [
                    null => [],
                    'card' => [],
                    'cheque' => [],
            ],
            'paymentAmount' => [
                    null => [],
                    0 => [],
                    110 => [],
                    55 => [],
                    27.5 => []
            ],
            'isRepeatCase' => [
                    'yes' => [],
                    'no' => []
            ],
            'receiveBenefits' => [
                    'yes' => [],
                    'no' => []
            ],
            'damageAwardOver16K' => [
                    'yes' => [],
                    'no' => []
            ],
            'lowIncome' => [
                    'yes' => [],
                    'no' => []
            ],
            'receiveUniversalCredit' => [
                    'yes' => [],
                    'no' => []
            ],
            'hasTrust' => [
                    null => [],
                    'primary' => [],
                    'replacement' => []
            ]
    ];
    
    
    public function generate()
    {
        $this->lpaType = $this->random(array(self::HW, self::PF));
        
        $this->lpa = array();
        $this->lpa['id'] = rand(1, 99999999999);
        $updated = time()-rand(0, 864000);
        $this->lpa['createdAt'] = date('c', $updated - rand(0, 864000));
        $this->lpa['updatedAt'] = date('c', $updated);
        $this->lpa['user'] = $this->rString(32, self::HEX_NUMBER);
        $this->lpa['repeatCaseNumber'] = $this->random(array(null, null, null, $this->rInt('random', array('min'=>10000000, 'max'=>99999999))));
        $this->lpa['payment'] = array (
                "reducedFeeReceivesBenefits" => $this->random(array(true, false)),
                "reducedFeeAwardedDamages" => $this->random(array(true, false)),
                "reducedFeeLowIncome" => $this->random(array(true, false)),
                "reducedFeeUniversalCredit" => $this->random(array(true, false)),
                "date" => date('c', $updated),
        );
        
        if($this->lpa['payment']['reducedFeeReceivesBenefits']) {
            if($this->lpa['payment']['reducedFeeAwardedDamages']) {
                if($this->lpa['payment']['reducedFeeUniversalCredit']) {
                    $this->lpa['payment']['amount'] = null;
                }
                else {
                    if($this->lpa['payment']['reducedFeeLowIncome']) {
                        $this->lpa['payment']['amount'] = 55;
                    }
                    else {
                        $this->lpa['payment']['amount'] = 110;
                    }
                }
            }
            else {
                $this->lpa['payment']['amount'] = 0;
            }
        }
        else {
            if($this->lpa['payment']['reducedFeeUniversalCredit']) {
                $this->lpa['payment']['amount'] = null;
            }
            else {
                if($this->lpa['payment']['reducedFeeLowIncome']) {
                    $this->lpa['payment']['amount'] = 55;
                }
                else {
                    $this->lpa['payment']['amount'] = 110;
                }
            }
        }
        
        if($this->lpa['repeatCaseNumber'] && $this->lpa['repeatCaseNumber'] && $this->lpa['payment']['amount']) {
            $this->lpa['payment']['amount'] = $this->lpa['payment']['amount']/2;
        }
        
        if($this->lpa['payment']['amount'] > 0) {
            $this->lpa['payment']['method'] = $this->random(array('card', 'cheque'));
            if($this->lpa['payment']['method'] == 'card') {
                $this->lpa['payment']["phone"] = array("number" => $this->rPhone());
                $this->lpa['payment']['reference'] = $this->rString('32', self::ALPHA_NUMBER);
                $this->lpa['payment']['date'] = date('c', $updated);
            }
        }
        else {
            $this->lpa['payment']['method'] = null;
        }
        
        $this->lpa['whoAreYouAnswered'] = true;
        $this->lpa['locked'] = true;
        $this->lpa['seed'] = null;
        $this->lpa['document'] = array(
                "type" => $this->lpaType,
                "donor" => $this->donor(),
                "primaryAttorneys" => $this->primaryAttorneys(),
                "certificateProvider" => $this->certificateProvider(),
                "peopleToNotify" => $this->peopleToNotify(),
                "instruction" => $this->instruction(),
                "preference" => $this->preference(),
                "primaryAttorneyDecisions" => $this->primaryAttorneyDecisions(),
                "replacementAttorneys" => $this->replacementAttorneys(),
                "replacementAttorneyDecisions" => $this->replacementAttorneyDecisions(),
                "whoIsRegistering" => $this->applicant(),
                "correspondent" => $this->correspondent(),
        );
        
        // update test coverage
        $this->updateCoverage();
        
        return $this->lpa;
    }
    
    protected function donor()
    {
        $this->donor = array(
                "name" => array(
                        "title" => $this->rTitle(),
                        "first" => $this->rForename(),
                        "last"  => $this->rSurname()
                        ),
                "otherNames"    => $this->random(array(null, null, null, $this->rForename())),
                "address"       => $this->rAddr(),
                "dob"           => array('date' => $this->rDob()),
                "email"         => $this->random(array(null, array('address' => $this->rEmail()))),
                "canSign"       => $this->random(array(true, true, false)),
        );
        
        return $this->donor;
    }
    
    protected function correspondent()
    {
        $who = $this->random(array('donor', 'attorney', 'other'));
        switch($who) {
            case 'donor':
                $this->correspondent =  array(
                    "who"            => $who,
                    "name"           => $this->donor['name'],
                    "company"        => null,
                    "address"        => $this->donor['address'],
                    "email"          => $this->donor['email'],
                    "phone"          => $this->random(array(null, array("number"=>$this->rPhone()))),
                    "contactByPost"  => $this->random(array(true, false)),
                    "contactInWelsh" => $this->random(array(true, false)),
                );
                break;
            case 'attorney':
                $attorney = $this->random($this->primaryAttorneys);
                $this->correspondent =  array(
                        "who"            => $who,
                        "name"           => ($attorney['type']=='human')?$attorney['name']:null,
                        "company"        => ($attorney['type']=='human')?null:$attorney['name'],
                        "address"        => $attorney['address'],
                        "email"          => $attorney['email'],
                        "phone"          => $this->random(array(null, array("number"=>$this->rPhone()))),
                        "contactByPost"  => $this->random(array(true, false)),
                        "contactInWelsh" => $this->random(array(true, false)),
                );
                break;
            case 'other':
                $this->correspondent =  array(
                        "who"   => $who,
                        "name"  => array(
                            "title"  => $this->rTitle(),
                            "first"  => $this->rForename(),
                            "last"   => $this->rSurname()
                        ),
                        "company"        => $this->rCompany(),
                        "address"        => $this->rAddr(),
                        "email"          => $this->random(array(null, array('address' => $this->rEmail()))),
                        "phone"          => $this->random(array(null, array("number"=>$this->rPhone()))),
                        "contactByPost"  => $this->random(array(true, false)),
                        "contactInWelsh" => $this->random(array(true, false))
                );
                break;
        }
        
        return $this->correspondent;
    }
    
    protected function applicant()
    {
        // all attorney ids
        $attorneyIds = [];
        foreach($this->primaryAttorneys as $attorney) {
            $attorneyIds[] = $attorney['id'];
        }
        
        // one attorney
        $option1 = [$this->random($attorneyIds)];
        
        if(count($this->primaryAttorneys) > 1) {
            
            // random pick attorneys 
            $option2 = $this->random($attorneyIds, true);
            
            // all attorneys
            $option3 = $attorneyIds;
            
            if($this->primaryAttorneyDecisions['how'] == 'jointly') {
                $this->applicant = $this->random(array(
                        'donor', $option3
                ));
            }
            else {
                $attorneys = $this->random(array($option1, $option2, $option3));
                $this->applicant = $this->random(array(
                        'donor', $attorneys, $attorneys, $attorneys,
                ));
            }
        }
        else {
            $this->applicant = $this->random(array(
                    'donor', $option1
            ));
        }
        
        return $this->applicant;
    }
    
    protected function primaryAttorneyDecisions()
    {
        $decisions = array();
        if(count($this->primaryAttorneys) > 1) {
            $decisions["how"] = $this->random(array("jointly", "jointly-attorney-severally", "depends"));
        }
        else {
            $decisions["how"] = "single-attorney";
        }
        
        if($this->lpaType == self::PF) {
            $decisions["when"] = $this->random(array('no-capacity', 'now'));
        }
        else {
            $decisions["canSustainLife"] = $this->random(array(true, false));
        }
        
        if($decisions['how'] == 'depends') {
            $decisions['howDetails'] = $this->random(array(
                    $this->rText(300, 1764),
                    $this->rText(1765, 3528),
                    $this->rText(3529, 5292),
            ));
        }
        
        $this->primaryAttorneyDecisions = $decisions;
        
        return $decisions;
    }
    
    protected function replacementAttorneyDecisions()
    {
        if(empty($this->replacementAttorneys)) return null;
        
        $hasMultiPrimaryAttorneys = (count($this->primaryAttorneys) > 1);
        $hasMultiReplacementAttorneys = (count($this->replacementAttorneys) > 1);
        
        $decisions = array();
        if($hasMultiReplacementAttorneys) {
            $decisions["how"] = $this->random(array("jointly", "jointly-attorney-severally", "depends"));
        }
        else {
            $decisions["how"] = "single-attorney";
        }
        
        $decisions["when"] = null;
        if($hasMultiPrimaryAttorneys) {
            $primaryDecision = $this->primaryAttorneyDecisions['how'];
            if($primaryDecision == 'jointly-attorney-severally') {
                $decisions["when"] = $this->random(array('first', 'last', 'depends'));
                if($hasMultiReplacementAttorneys) {
                    if($decisions["when"] != 'last') {
                        $decisions["how"] = 'jointly-attorney-severally';
                    }
                }
            }
        }
        
        $decisions["howDetails"] = null;
        $decisions["whenDetails"] = null;
        
        if($decisions['how'] == 'depends') {
            $decisions['howDetails'] = $this->random(array(
                    $this->rText(300, 1764),
                    $this->rText(1765, 3528),
                    $this->rText(3529, 5292),
            ));
        }
        
        if($decisions['when'] == 'depends') {
            $decisions['whenDetails'] = $this->random(array(
                    $this->rText(300, 1764),
                    $this->rText(1765, 3528),
                    $this->rText(3529, 5292),
            ));
        }
        
        $this->replacementAttorneyDecisions = $decisions;
        
        return $decisions;
    }
    
    protected function instruction()
    {
        return $this->random(array(
                $this->rText(300, 924),
                $this->rText(925, 2706),
                $this->rText(2707, 4470),
        ));
    }
    
    protected function preference()
    {
        return $this->random(array(
                $this->rText(300, 924),
                $this->rText(925, 2706),
                $this->rText(2707, 4470),
        ));
    }
    
    protected function certificateProvider()
    {
        $this->certificateProvider = array(
                "name" => array(
                        "title" => $this->rTitle(),
                        "first" => $this->rForename(),
                        "last"  => $this->rSurname()
                ),
                "address"       => $this->rAddr()
        );
        
        return $this->certificateProvider;
        
    }
    
    protected function primaryAttorneys($numAttorneys = null)
    {
        if($numAttorneys == null) {
            $numAttorneys = rand(1, 10);
        }
        
        for($i=0; $i<$numAttorneys; $i++) {
            if(($this->lpaType == self::HW) || $this->hasTrust) {
                $type = 'human';
            }
            else {
                $type = $this->random(array('human','human','human','human','human','human','human','trust'));
                if($type == 'trust') {
                    $this->hasTrust = 'primary';
                }
            }
            
            $attorney = array(
                    "id"        => $this->rInt('seq', array('name'=>'attorney_id', 'start'=>0)),
                    "address"   => $this->rAddr(),
                    "email"     => $this->random(array(null, array('address' => $this->rEmail()))),
                    'type'      => $type
            );
            
            if($type == 'human') {
                $attorney["name"] = array(
                        "title" => $this->rTitle(),
                        "first" => $this->rForename(),
                        "last"  => $this->rSurname()
                );
                
                $attorney["dob"] = array('date' => $this->rDob());
            }
            else {
                $attorney["name"] = $this->rCompany();
                $attorney["number"] = strtoupper($this->rString(8, self::ALPHA_NUMBER));
            }
            
            $this->primaryAttorneys[] = $attorney;
        }
        
        return $this->primaryAttorneys;
    }
    
    protected function replacementAttorneys()
    {
        $numAttorneys = rand(0, 3);
        
        for($i=0; $i<$numAttorneys; $i++) {
            if(($this->lpaType == self::HW) || $this->hasTrust) {
                $type = 'human';
            }
            else {
                $type = $this->random(array('human','human','human','trust'));
                if($type == 'trust') {
                    $this->hasTrust = 'replacement';
                }
            }
            
            $attorney = array(
                    "id"        => $this->rInt('seq', array('name'=>'replacement_attorney_id', 'start'=>0)),
                    "address"   => $this->rAddr(),
                    'type'      => $type
            );
            
            if($type == 'human') {
                $attorney["name"] = array(
                        "title" => $this->rTitle(),
                        "first" => $this->rForename(),
                        "last"  => $this->rSurname(),
                );
                
                $attorney["dob"] = array('date' => $this->rDob());
            }
            else {
                $attorney["name"]   = $this->rCompany();
                $attorney["number"] = strtoupper($this->rString(8, self::ALPHA_NUMBER));
            }
            
            $this->replacementAttorneys[] = $attorney;
        }
        
        return $this->replacementAttorneys;
    }
    
    protected function peopleToNotify()
    {
        $numNotified = rand(0, 5);
        
        for($i=0; $i<$numNotified; $i++) {
            $this->peopleToNotify[] = array(
                    "id"        => $this->rInt('seq', array('name'=>'notified_id', 'start'=>0)),
                    "name"      => array(
                            "title" => $this->rTitle(),
                            "first" => $this->rForename(),
                            "last"  => $this->rSurname()
                    ),
                    "address"   => $this->rAddr()
            );
        }
        
        return $this->peopleToNotify;
    }
    
    public function getFileName()
    {
        $name = "";
        if($this->lpaType == self::HW) $name .= 'hw-';
        if($this->lpaType == self::PF) $name .= 'pf-';
        
        $name .= count($this->primaryAttorneys) . 'at-';
        
        if($this->hasTrust == 'primary') {
            $name .= 'tp-';
        }
        
        switch($this->lpa['document']['primaryAttorneyDecisions']['how']) {
            case 'jointly':
                $name .= 'jt-';
                break;
            case 'jointly-attorney-severally':
                $name .= 'js-';
                break;
            case 'depends':
                $name .= 'dp-';
                break;
        }
        
        $name .= count($this->replacementAttorneys) . 'ra-';
        
        if($this->hasTrust == 'replacement') {
            $name .= 'tr-';
        }
        
        switch($this->lpa['document']['replacementAttorneyDecisions']['how']) {
            case 'jointly':
                $name .= 'jt-';
                break;
            case 'jointly-attorney-severally':
                $name .= 'js-';
                break;
            case 'depends':
                $name .= 'dp-';
                break;
        }
        
        $name .= count($this->peopleToNotify) . 'np-';
        
        if(is_array($this->applicant)) {
            $name .= count($this->applicant) . 'ap-';
        }
        else {
            $name .= 'dap-';
        }
        
        if($this->lpa['payment']['method'] == 'card') {
            $name .= 'cd';
        }
        elseif($this->lpa['payment']['method'] == 'cheque') {
            $name .= 'ch';
        }
        
        $name .= '.json';
        
        return $name;
    }
    
    /**
     * Check test coverage
     */
    protected function updateCoverage()
    {
        static::$coverage['type'][$this->lpa['document']['type']][] = $this->getFileName();
        static::$coverage['canSign'][$this->lpa['document']['donor']['canSign']][] = $this->getFileName();
        static::$coverage['primaryAttorney'][count($this->lpa['document']['primaryAttorneys'])][] = $this->getFileName();
        static::$coverage['replacementAttorney'][count($this->lpa['document']['replacementAttorneys'])][] = $this->getFileName();
        
        static::$coverage['hasTrust'][$this->hasTrust][] = $this->getFileName();
        
        static::$coverage['peopleToNotify'][count($this->lpa['document']['peopleToNotify'])][] = $this->getFileName();
        if(is_array($this->lpa['document']['whoIsRegistering'])) {
            static::$coverage['applicant'][count($this->lpa['document']['whoIsRegistering'])][] = $this->getFileName();
        }
        else {
            static::$coverage['applicant']['donor'][] = $this->getFileName();
        }
        static::$coverage['correspondentIs'][$this->lpa['document']['correspondent']['who']][] = $this->getFileName();
        
        static::$coverage['contactByPost'][$this->lpa['document']['correspondent']['contactByPost']?'yes':'no'][] = $this->getFileName();
        
        static::$coverage['contactByPhone'][$this->lpa['document']['correspondent']['phone']?'yes':'no'][] = $this->getFileName();
        
        static::$coverage['contactByEmail'][$this->lpa['document']['correspondent']['email']?'yes':'no'][] = $this->getFileName();
        
        static::$coverage['contactInWelsh'][$this->lpa['document']['correspondent']['contactInWelsh']?'yes':'no'][] = $this->getFileName();
        
        if(strlen($this->lpa['document']['instruction']) <= 11*84) {
            static::$coverage['numOfInstructionPages'][1][] = $this->getFileName();
        }
        elseif((strlen($this->lpa['document']['instruction']) > 11*84) && ( strlen($this->lpa['document']['instruction']) <= (11*84 + 21*84) )) {
            static::$coverage['numOfInstructionPages'][2][] = $this->getFileName();
        }
        else {
            static::$coverage['numOfInstructionPages'][3][] = $this->getFileName();
        }
        
        if(strlen($this->lpa['document']['preference']) <= 11*84) {
            static::$coverage['numOfPreferencePages'][1][] = $this->getFileName();
        }
        elseif((strlen($this->lpa['document']['preference']) > 11*84) && ( strlen($this->lpa['document']['preference']) <= (11*84 + 21*84) )) {
            static::$coverage['numOfPreferencePages'][2][] = $this->getFileName();
        }
        else {
            static::$coverage['numOfPreferencePages'][3][] = $this->getFileName();
        }
        
        static::$coverage['howPrimaryAttorneyAct'][$this->lpa['document']['primaryAttorneyDecisions']['how']][] = $this->getFileName();
        
        if($this->lpa['document']['primaryAttorneyDecisions']['how'] == 'depends') {
            if(strlen($this->lpa['document']['primaryAttorneyDecisions']['howDetails']) <= 21*84) {
                static::$coverage['numOfAttorneyDecisionPages'][1][] = $this->getFileName();
            }
            elseif((strlen($this->lpa['document']['primaryAttorneyDecisions']['howDetails']) > 21*84) && (strlen($this->lpa['document']['primaryAttorneyDecisions']['howDetails']) <= 2*21*84)) {
                static::$coverage['numOfAttorneyDecisionPages'][2][] = $this->getFileName();
            }
            else {
                static::$coverage['numOfAttorneyDecisionPages'][3][] = $this->getFileName();
            }
        }
        
        if($this->lpa['document']['type'] == self::PF) {
            static::$coverage['whenAttorneyMakeDecision'][$this->lpa['document']['primaryAttorneyDecisions']['when']][] = $this->getFileName();
        }
        else {
            static::$coverage['life-sustaining'][$this->lpa['document']['primaryAttorneyDecisions']['canSustainLife']?'yes':'no'][] = $this->getFileName();
        }
        
        static::$coverage['howReplacementAttorneyAct'][$this->lpa['document']['replacementAttorneyDecisions']['how']][] = $this->getFileName();
        
        if($this->lpa['document']['replacementAttorneyDecisions']['how'] == 'depends') {
            if(strlen($this->lpa['document']['replacementAttorneyDecisions']['howDetails']) <= 21*84) {
                static::$coverage['numOfReplacementAttorneyDecisionPages'][1][] = $this->getFileName();
            }
            elseif((strlen($this->lpa['document']['replacementAttorneyDecisions']['howDetails']) > 21*84) && (strlen($this->lpa['document']['replacementAttorneyDecisions']['howDetails']) < 2*21*84)) {
                static::$coverage['numOfReplacementAttorneyDecisionPages'][2][] = $this->getFileName();
            }
            else {
                static::$coverage['numOfReplacementAttorneyDecisionPages'][3][] = $this->getFileName();
            }
        }
        
        static::$coverage['whenReplacementStepIn'][$this->lpa['document']['replacementAttorneyDecisions']['when']][] = $this->getFileName();
        
        if($this->lpa['document']['replacementAttorneyDecisions']['when'] == 'depends') {
            if(strlen($this->lpa['document']['replacementAttorneyDecisions']['whenDetails']) <= 21*84) {
                static::$coverage['numOfReplacementStepInDecisionPages'][1][] = $this->getFileName();
            }
            elseif((strlen($this->lpa['document']['replacementAttorneyDecisions']['whenDetails']) > 21*84) && (strlen($this->lpa['document']['replacementAttorneyDecisions']['whenDetails']) < 2*21*84)) {
                static::$coverage['numOfReplacementStepInDecisionPages'][2][] = $this->getFileName();
            }
            else {
                static::$coverage['numOfReplacementStepInDecisionPages'][3][] = $this->getFileName();
            }
        }
        
        static::$coverage['paymentMethod'][$this->lpa['payment']['method']][] = $this->getFileName();
        static::$coverage['paymentAmount'][$this->lpa['payment']['amount']][] = $this->getFileName();
        
        static::$coverage['isRepeatCase'][$this->lpa['repeatCaseNumber']?'yes':'no'][] = $this->getFileName();
        
        static::$coverage['receiveBenefits'][($this->lpa['payment']['reducedFeeReceivesBenefits'])?'yes':'no'][] = $this->getFileName();
        static::$coverage['damageAwardOver16K'][$this->lpa['payment']['reducedFeeAwardedDamages']?'yes':'no'][] = $this->getFileName();
        static::$coverage['lowIncome'][$this->lpa['payment']['reducedFeeLowIncome']?'yes':'no'][] = $this->getFileName();
        static::$coverage['receiveUniversalCredit'][$this->lpa['payment']['reducedFeeUniversalCredit']?'yes':'no'][] = $this->getFileName();
        
    }
    
    public function coverageSummary()
    {
        $summary = [];
        foreach(self::$coverage as $subjectName=>$subject) {
            $summary[$subjectName] = [];
            foreach($subject as $key=>$values) {
                $summary[$subjectName][$key] = count($values);
            }
        }
//         print_r($summary);
        
        return print_r($summary, true);
    }
    
    public function isAllCoverred()
    {
        foreach(self::$coverage as $subjectName=>$subject) {
            foreach($subject as $key=>$values) {
                if(count($values) == 0) {
                    return false;
                }
            }
        }
        
        return true;
    }
}


for($i=0; $i<200; $i++) {
    
    $generator = new JsonGenerator();
    $data = $generator->generate();
    
    $lpaJson = json_encode($data, JSON_PRETTY_PRINT);
     
    $lpa = new Lpa($lpaJson);
    
    if($lpa->validate()->hasErrors()) {
        echo $lpaJson. PHP_EOL;
        print_r($lpa->validate());
        echo "Validation error!".PHP_EOL;
        exit;
    }
    
    if(!$lpa->isComplete()) {
        echo 'incomplete LPA'. PHP_EOL; 
    }
    
    $filepath = $generator->getFileName();

    if( !is_dir(__DIR__.'/../test-data/json') ){
        mkdir( __DIR__.'/../test-data/json', 0777, true );
    }
    
    file_put_contents(__DIR__.'/../test-data/json/'.$filepath, $lpaJson);
    
    if($generator->isAllCoverred()) {
        echo "Fully coverred after ".($i+1)." files have been generated".PHP_EOL;
        break;
    }
}

file_put_contents(__DIR__.'/../test-data/json/coverage-summary.txt', $generator->coverageSummary());
file_put_contents(__DIR__.'/../test-data/json/coverage.txt', print_r($generator::$coverage, true));