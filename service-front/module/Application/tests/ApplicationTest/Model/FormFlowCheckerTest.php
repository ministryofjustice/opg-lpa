<?php
namespace ApplicationTest\Model;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;

/**
 * FormFlowChecker test case.
 */
class FormFlowCheckerTest extends AbstractHttpControllerTestCase
{

    /**
     * @var Application\Model\FormFlowChecker $checker
     */
    private $checker;
    
    /**
     * @var Opg\Lpa\DataModel\Lpa\Lpa $lpa
     */
    private $lpa;
    
    /**
     * @var string Document::LPA_TYPE_PF | Document::LPA_TYPE_HW
     */
    private $formType;
    
    /**
     * @var PrimaryAttorneyDecisions
     */
    private $primaryAttorneyDecisions;
    
    /**
     * @var ReplacementAttorneyDecisions
     */
    private $replacementAttorneyDecisions;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        
        // set default form type
        $this->formType = Document::LPA_TYPE_PF;
        
        $this->lpa = $this->initLpa();
        
        $this->primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
        $this->replacementAttorneyDecisions = new ReplacementAttorneyDecisions();
        
        $this->checker = new FormFlowChecker($this->lpa);
    }
    
    public function testRouteFormType()
    {
        $this->assertEquals('lpa/form-type', $this->checker->check('lpa/form-type'));
    }
    
    public function testRouteFormTypeFallback()
    {
        $this->lpa->document = null;
        $this->assertEquals('user/dashboard', $this->checker->check('lpa/form-type'));
    }
    
    public function testRouteDonor()
    {
        $this->setLpaTypePF();
        $this->assertEquals('lpa/donor', $this->checker->check('lpa/donor'));
    }
    
    public function testRouteDonorFallback()
    {
        $this->assertEquals('lpa/form-type', $this->checker->check('lpa/donor'));
    }
    
    public function testRouteDonorAdd()
    {
        $this->setLpaTypePF();
        $this->assertEquals('lpa/donor/add', $this->checker->check('lpa/donor/add'));
    }
    
    public function testRouteDonorEdit()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/donor/edit', $this->checker->check('lpa/donor/edit'));
    }
    
    public function testRouteWhenLpaStarts()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->check('lpa/when-lpa-starts'));
    }
    
    public function testRouteWhenLpaStartsOnHwTypeLpa()
    {
        $this->formType = document::LPA_TYPE_HW;
        $this->setLpaDonor();
        $this->assertEquals('lpa/donor', $this->checker->check('lpa/when-lpa-starts'));
    }
    
    public function testRouteLifeSustaining()
    {
        $this->formType = document::LPA_TYPE_HW;
        $this->setLpaDonor();
        $this->assertEquals('lpa/life-sustaining', $this->checker->check('lpa/life-sustaining'));
    }
    
    public function testRouteLifeSustainingWithPfTypeLpa()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/donor', $this->checker->check('lpa/life-sustaining'));
    }
    
    public function testRouteAttorneyWithPfTypeLpa()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/primary-attorney'));
    }
    
    public function testRouteAttorneyWithHwTypeLpa()
    {
        $this->setLifeSustaining();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/primary-attorney'));
    }
    
    public function testRouteAttorneyFallback()
    {
        $this->assertEquals('lpa/form-type', $this->checker->check('lpa/primary-attorney'));
        
        $this->setLpaTypePF();
        $this->assertEquals('lpa/donor', $this->checker->check('lpa/primary-attorney'));
        
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->check('lpa/primary-attorney'));
    }
    
    public function testRouteAttorneyAdd()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney/add', $this->checker->check('lpa/primary-attorney/add'));
    }
    
    public function testRouteAttorneyAddFallback()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->check('lpa/primary-attorney/add'));
    }

    public function testRouteAttorneyEdit()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney/edit', $this->checker->check('lpa/primary-attorney/edit', 0));
    }

    public function testRouteAttorneyEditFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/primary-attorney/edit', 0));
        
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/primary-attorney/edit', 1));
    }
    
    public function testRouteAttorneyDelete()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney/delete', $this->checker->check('lpa/primary-attorney/delete', 0));
    }

    public function testRouteAttorneyDeleteFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/primary-attorney/delete', 0));
        
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/primary-attorney/delete', 1));
    }
    
    public function testRouteHowPrimaryAttorneysMakeDecision()
    {
        $this->addPrimaryAttorney(2);
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->check('lpa/how-primary-attorneys-make-decision'));
    }
    
    public function testRouteHowPrimaryAttorneysMakeDecisionFallback()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->check('lpa/how-primary-attorneys-make-decision'));
        
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/how-primary-attorneys-make-decision'));
        
        $this->addPrimaryAttorney(1);
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/how-primary-attorneys-make-decision'));
    }
    
    public function testRouteReplacementAttorney()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/replacement-attorney'));
        
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/replacement-attorney'));
    }

    public function testRouteReplacementAttorneyFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/replacement-attorney'));
        
        $this->addPrimaryAttorney(2);
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->check('lpa/replacement-attorney'));
    }
    
    public function testRouteReplacementAttorneyAdd()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney/add', $this->checker->check('lpa/replacement-attorney/add'));
    
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/replacement-attorney/add', $this->checker->check('lpa/replacement-attorney/add'));
    }

    public function testRouteReplacementAttorneyAddFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/replacement-attorney/add'));
        
        $this->addPrimaryAttorney(2);
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->check('lpa/replacement-attorney/add'));
    }

    public function testRouteReplacementAttorneyEdit()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney/edit', $this->checker->check('lpa/replacement-attorney/edit', 0));
    }

    public function testRouteReplacementAttorneyEditFallback()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/replacement-attorney/edit', 0));
        
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->check('lpa/replacement-attorney/edit', 0));
        
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/replacement-attorney/edit', 1));
    }

    public function testRouteReplacementAttorneyDelete()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney/delete', $this->checker->check('lpa/replacement-attorney/delete', 0));
    }
    
    public function testRouteReplacementAttorneyDeleteFallback()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/replacement-attorney/delete', 0));
    
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->check('lpa/replacement-attorney/delete', 0));
    
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/replacement-attorney/delete', 1));
    }
    
    public function testRouteWhenReplacementAttorneyStepIn()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->check('lpa/when-replacement-attorney-step-in'));
    }

    public function testRouteWhenReplacementAttorneyStepInFallback()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/when-replacement-attorney-step-in'));
        
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/when-replacement-attorney-step-in'));
        
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/when-replacement-attorney-step-in'));
        
        $this->setPrimaryAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/when-replacement-attorney-step-in'));
    }
    
    public function testRouteHowReplacementAttorneysMakeDecision()
    {
        $this->addReplacementAttorney(2);
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->check('lpa/how-replacement-attorneys-make-decision'));
        
        $this->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->check('lpa/how-replacement-attorneys-make-decision'));
    }

    public function testRouteHowReplacementAttorneysMakeDecisionFallback()
    {
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/how-replacement-attorneys-make-decision'));
        
        $this->setReplacementAttorneysStepInWhenFirstPrimaryUnableAct();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/how-replacement-attorneys-make-decision'));
        
        $this->addPrimaryAttorney(2);
        $this->addReplacementAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->check('lpa/how-replacement-attorneys-make-decision'));
    }
    
    public function testRouteCertificateProvider1()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }
    
    public function testRouteCertificateProvider2()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider3()
    {
        $this->addPrimaryAttorney();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider4()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider5()
    {
        $this->addPrimaryAttorney();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider6()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider7()
    {
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->setReplacementAttorneysStepInWhenFirstPrimaryUnableAct();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        
        $this->lpa->document->replacementAttorneyDecisions->when = ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST;
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        
        $this->lpa->document->replacementAttorneyDecisions->when = ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS;
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider8()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysStepInWhenFirstPrimaryUnableAct();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    
        $this->setReplacementAttorneysStepInDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }
    
    public function testRouteCertificateProvider9()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/certificate-provider'));
    }
    
    public function testRouteCertificateProviderFallback1()
    {
        $this->addPrimaryAttorney();
        $this->addReplacementAttorney(2);
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProviderFallback2()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->addReplacementAttorney(2);
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProviderFallback3()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProviderFallback4()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->check('lpa/certificate-provider'));
        
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->check('lpa/certificate-provider'));
    }

    public function testRouteCertificateProviderFallback5()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->check('lpa/certificate-provider'));
    }
    
    public function testRoutePeopleToNotify()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify', $this->checker->check('lpa/people-to-notify'));
    }
    
    public function testRoutePeopleToNotifyFallback()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/people-to-notify'));
    }
    
    public function testRouteInstructions()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/instructions', $this->checker->check('lpa/instructions'));
        
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/instructions', $this->checker->check('lpa/instructions'));
    }
    
    public function testRouteInstructionsFallback()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->check('lpa/instructions'));
    }
    
    public function testRouteCreated()
    {
        $this->addPeopleToNotify();
        $this->lpa->document->instruction = '...instructions...';
        $this->assertEquals('lpa/created', $this->checker->check('lpa/created'));
        
        $this->lpa->document->instruction = false;
        $this->assertEquals('lpa/created', $this->checker->check('lpa/created'));
    }

    public function testRouteCreatedFallback()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/instructions', $this->checker->check('lpa/created'));
    }
    
    public function testRouteApplicant()
    {
        $this->setLpaCreated();
        $this->assertEquals('lpa/applicant', $this->checker->check('lpa/applicant'));
    }
    
    public function testRouteApplicantFallback()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/instructions', $this->checker->check('lpa/applicant'));
        
        $this->setLpaInstructons();
        $this->assertEquals('lpa/created', $this->checker->check('lpa/applicant'));
    }
    
    public function testRouteCorrespondent()
    {
        $this->
        $this->assertEquals('lpa/correspondent', $this->checker->check('lpa/correspondent'));
    }
    
    public function testRouteCorrespondentFallback()
    {
        
    }
    
############################## Private methods ###########################################################################

    private function setLpaApplicant()
    {
        $this->setLpaCreated();
        $this->lpa->document->applicant = 'donor';
        $this->lpa->document->correspondent = new Correspondence();
    }
    
    private function setLpaCreated()
    {
        $this->setLpaInstructons();
        $this->lpa->completedAt = new \DateTime();
    }
    
    private function setLpaInstructons()
    {
        $this->addPeopleToNotify();
        $this->lpa->document->instruction = '...instructions...';
    }
    
    private function addPeopleToNotify($count=1)
    {
        if($this->lpa->document->certificateProvider == null) {
            $this->addCertificateProvider();
        }
        
        for($i=0; $i<$count; $i++) {
            $this->lpa->document->peopleToNotify[] = new NotifiedPerson();
        }
    }

    private function addCertificateProvider()
    {
        $this->addReplacementAttorney();
        $this->lpa->document->certificateProvider = new CertificateProvider();
    }
    
    private function setReplacementAttorneysMakeDecisionJointlySeverally()
    {
        if(count($this->lpa->document->replacementAttorneys) < 2) {
            $this->addReplacementAttorney(2-count($this->lpa->document->replacementAttorneys));
        }
        
        $this->lpa->document->replacementAttorneyDecisions = $this->setReplacementAttorneyDecisions([
            'how'   => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ] );
    }

    private function setReplacementAttorneysMakeDecisionJointly()
    {
        if(count($this->lpa->document->replacementAttorneys) < 2) {
            $this->addReplacementAttorney(2-count($this->lpa->document->replacementAttorneys));
        }
        
        $this->lpa->document->replacementAttorneyDecisions = $this->setReplacementAttorneyDecisions([
            'how'   => AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
        ]);
    }

    private function setReplacementAttorneysMakeDecisionDepends()
    {
        if(count($this->lpa->document->replacementAttorneys) < 2) {
            $this->addReplacementAttorney(2-count($this->lpa->document->replacementAttorneys));
        }
        
        $this->lpa->document->replacementAttorneyDecisions = $this->setReplacementAttorneyDecisions([
            'how'   => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
        ]);
    }
    
    private function setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney(2);
        $this->lpa->document->replacementAttorneyDecisions = $this->setReplacementAttorneyDecisions([
            'when'   => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ]);
    }
    
    private function setReplacementAttorneysStepInDepends()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->lpa->document->replacementAttorneyDecisions = $this->setReplacementAttorneyDecisions([
            'when'   => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
        ]);
    }
    
    private function setReplacementAttorneysStepInWhenFirstPrimaryUnableAct()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->lpa->document->replacementAttorneyDecisions = $this->setReplacementAttorneyDecisions([
            'when'   => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST,
        ]);
    }
    
    private function addReplacementAttorney($count=1)
    {
        if($this->lpa->document->primaryAttorneys == null) {
            $this->addPrimaryAttorney();
        }
        
        for($i=0; $i<$count; $i++) {
            $this->lpa->document->replacementAttorneys[] = new Human();
        }
    }
    
    private function setPrimaryAttorneysMakeDecisionJointlySeverally()
    {
        if(count($this->lpa->document->primaryAttorneys) < 2) {
            $this->addPrimaryAttorney(2-count($this->lpa->document->primaryAttorneys));
        }
        
        $this->lpa->document->primaryAttorneyDecisions = $this->setPrimaryAttorneyDecisions([
            'how'   => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ] );
    }

    private function setPrimaryAttorneysMakeDecisionJointly()
    {
        if(count($this->lpa->document->primaryAttorneys) < 2) {
            $this->addPrimaryAttorney(2-count($this->lpa->document->primaryAttorneys));
        }
        
        $this->lpa->document->primaryAttorneyDecisions = $this->setPrimaryAttorneyDecisions([
            'how'   => AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
        ]);
    }

    private function setPrimaryAttorneysMakeDecisionDepends()
    {
        if(count($this->lpa->document->primaryAttorneys) < 2) {
            $this->addPrimaryAttorney(2-count($this->lpa->document->primaryAttorneys));
        }
        
        $this->lpa->document->primaryAttorneyDecisions = $this->setPrimaryAttorneyDecisions([
            'how'   => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
        ]);
    }
    
    private function addPrimaryAttorney($count=1)
    {
        $this->setWhenLpaStarts();
        for($i=0; $i<$count; $i++) {
            $this->lpa->document->primaryAttorneys[] = new Human();
        }
    }
    
    private function setLifeSustaining()
    {
        $this->formType = Document::LPA_TYPE_HW;
        if($this->lpa->document->donor == null) {
            $this->setLpaDonor();
        }
        
        $this->lpa->document->primaryAttorneyDecisions = $this->setPrimaryAttorneyDecisions([
            'canSustainLife' => true,
        ]);
    }

    private function setWhenLpaStarts()
    {
        if($this->lpa->document->donor == null) {
            $this->setLpaDonor();
        }
        
        $this->lpa->document->primaryAttorneyDecisions = $this->setPrimaryAttorneyDecisions([
            'when'  => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
        ]);
    }
    
    private function setLpaDonor()
    {
        if($this->formType == Document::LPA_TYPE_HW) {
            $this->setLpaTypeHW();
        }
        else {
            $this->setLpaTypePF();
        }
        
        $this->lpa->document->donor = new Donor();
    }
    
    private function setLpaTypePF()
    {
        $this->lpa->document->type = Document::LPA_TYPE_PF;
    }
    
    private function setLpaTypeHW()
    {
        $this->lpa->document->type = Document::LPA_TYPE_HW;
    }
    
    private function setPrimaryAttorneyDecisions($params)
    {
        foreach($params as $property => $value) {
            if(property_exists($this->primaryAttorneyDecisions, $property)) {
                $this->primaryAttorneyDecisions->$property = $value;
            }
            else {
                throw new \RuntimeException('Unknow property for primaryAttorneyDecisions: ' . $property);
            }
        }
        
        return $this->primaryAttorneyDecisions;
    }

    private function setReplacementAttorneyDecisions($params)
    {
        foreach($params as $property => $value) {
            if(property_exists($this->replacementAttorneyDecisions, $property)) {
                $this->replacementAttorneyDecisions->$property = $value;
            }
            else {
                throw new \RuntimeException('Unknow property for replacementAttorneyDecisions: ' . $property);
            }
        }
        
        return $this->replacementAttorneyDecisions;
    }
    
    private function initLpa()
    {
        $this->lpa = new Lpa([
        'id'                => rand(100000, 9999999999),
        'createdAt'         => new \DateTime(),
        'updatedAt'         => new \DateTime(),
        'user'              => rand(10000, 9999999),
        'locked'            => false,
        'whoAreYouAnswered' => false,
        'document'          => new Document(),
        ]);
        
        return $this->lpa;
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->FormFlowChecker = null;
        
        parent::tearDown();
    }
}

