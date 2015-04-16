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
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Application\Model\Service\Lpa\Metadata;

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
    
    public function testRouteLpa()
    {
        $this->assertEquals('lpa', $this->checker->getNearestAccessibleRoute('lpa'));
    }

    public function testRouteLpaFallback()
    {
        $this->lpa->document = null;
        $this->assertEquals('user/dashboard', $this->checker->getNearestAccessibleRoute('lpa'));
    }
    
    public function testRouteFormType()
    {
        $this->assertEquals('lpa/form-type', $this->checker->getNearestAccessibleRoute('lpa/form-type'));
    }
    
    public function testRouteFormTypeFallback()
    {
        $this->lpa->document = null;
        $this->assertEquals('user/dashboard', $this->checker->getNearestAccessibleRoute('lpa/form-type'));
    }
    
    public function testRouteDonor()
    {
        $this->setLpaTypePF();
        $this->assertEquals('lpa/donor', $this->checker->getNearestAccessibleRoute('lpa/donor'));
    }
    
    public function testRouteDonorFallback()
    {
        $this->assertEquals('lpa/form-type', $this->checker->getNearestAccessibleRoute('lpa/donor'));
    }
    
    public function testRouteDonorAdd()
    {
        $this->setLpaTypePF();
        $this->assertEquals('lpa/donor/add', $this->checker->getNearestAccessibleRoute('lpa/donor/add'));
    }
    
    public function testRouteDonorEdit()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/donor/edit', $this->checker->getNearestAccessibleRoute('lpa/donor/edit'));
    }
    
    public function testRouteWhenLpaStarts()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->getNearestAccessibleRoute('lpa/when-lpa-starts'));
    }
    
    public function testRouteWhenLpaStartsOnHwTypeLpa()
    {
        $this->formType = document::LPA_TYPE_HW;
        $this->setLpaDonor();
        $this->assertEquals('lpa/donor', $this->checker->getNearestAccessibleRoute('lpa/when-lpa-starts'));
    }
    
    public function testRouteLifeSustaining()
    {
        $this->formType = document::LPA_TYPE_HW;
        $this->setLpaDonor();
        $this->assertEquals('lpa/life-sustaining', $this->checker->getNearestAccessibleRoute('lpa/life-sustaining'));
    }
    
    public function testRouteLifeSustainingWithPfTypeLpa()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/donor', $this->checker->getNearestAccessibleRoute('lpa/life-sustaining'));
    }
    
    public function testRoutePrimaryAttorneyWithPfTypeLpa()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney'));
    }
    
    public function testRoutePrimaryAttorneyWithHwTypeLpa()
    {
        $this->setLifeSustaining();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney'));
    }
    
    public function testRoutePrimaryAttorneyFallback()
    {
        $this->assertEquals('lpa/form-type', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney'));
        
        $this->setLpaTypePF();
        $this->assertEquals('lpa/donor', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney'));
        
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney'));
    }
    
    public function testRoutePrimaryAttorneyAdd()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney/add', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/add'));
    }
    
    public function testRoutePrimaryAttorneyAddFallback()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/add'));
    }

    public function testRoutePrimaryAttorneyEdit()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney/edit', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/edit', 0));
    }

    public function testRoutePrimaryAttorneyEditFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/edit', 0));
        
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/edit', 1));
    }
    
    public function testRoutePrimaryAttorneyDelete()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney/delete', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/delete', 0));
    }

    public function testRoutePrimaryAttorneyDeleteFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/delete', 0));
        
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/delete', 1));
    }
    
    public function testRoutePrimaryAttorneyAddTrust()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney/add-trust', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/add-trust'));
    }
    
    public function testRoutePrimaryAttorneyAddTrustFallback()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/add-trust'));
    }
    
    public function testRoutePrimaryAttorneyEditTrust()
    {
        $this->addPrimaryAttorneyTrust();
        $this->assertEquals('lpa/primary-attorney/edit-trust', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/edit-trust'));
    }
    
    public function testRoutePrimaryAttorneyEditTrustFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/edit-trust'));
    
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/edit-trust'));
    }
    
    public function testRoutePrimaryAttorneyDeleteTrust()
    {
        $this->addPrimaryAttorneyTrust();
        $this->assertEquals('lpa/primary-attorney/delete-trust', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/delete-trust'));
    }
    
    public function testRoutePrimaryAttorneyDeleteTrustFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/delete-trust'));
    
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/delete-trust'));
    }
    
    public function testRouteHowPrimaryAttorneysMakeDecision()
    {
        $this->addPrimaryAttorney(2);
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/how-primary-attorneys-make-decision'));
    }
    
    public function testRouteHowPrimaryAttorneysMakeDecisionFallback()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->getNearestAccessibleRoute('lpa/how-primary-attorneys-make-decision'));
        
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/how-primary-attorneys-make-decision'));
        
        $this->addPrimaryAttorney(1);
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/how-primary-attorneys-make-decision'));
    }
    
    public function testRouteReplacementAttorney()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney'));
        
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney'));
    }

    public function testRouteReplacementAttorneyFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney'));
        
        $this->addPrimaryAttorney(2);
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney'));
    }
    
    public function testRouteReplacementAttorneyAdd()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney/add', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add'));
    
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/replacement-attorney/add', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add'));
    }

    public function testRouteReplacementAttorneyAddFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add'));
        
        $this->addPrimaryAttorney(2);
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add'));
    }

    public function testRouteReplacementAttorneyEdit()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney/edit', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/edit', 0));
    }

    public function testRouteReplacementAttorneyEditFallback()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/edit', 0));
        
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/edit', 0));
        
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/edit', 1));
    }

    public function testRouteReplacementAttorneyDelete()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney/delete', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/delete', 0));
    }
    
    public function testRouteReplacementAttorneyDeleteFallback()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/delete', 0));
    
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/delete', 0));
    
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/delete', 1));
    }
    
    public function testRouteReplacementAttorneyAddTrust()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney/add-trust', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add-trust'));
    
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/replacement-attorney/add-trust', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add-trust'));
    }
    
    public function testRouteReplacementAttorneyAddTrustFallback()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add-trust'));
    
        $this->addPrimaryAttorney(2);
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add-trust'));
    }
    
    public function testRouteReplacementAttorneyEditTrust()
    {
        $this->addReplacementAttorneyTrust();
        $this->assertEquals('lpa/replacement-attorney/edit-trust', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/edit-trust'));
    }
    
    public function testRouteReplacementAttorneyEditTrustFallback()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/edit-trust'));
    
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/edit-trust'));
    
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/edit-trust'));
    }
    
    public function testRouteReplacementAttorneyDeleteTrust()
    {
        $this->addReplacementAttorneyTrust();
        $this->assertEquals('lpa/replacement-attorney/delete-trust', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/delete-trust'));
    }
    
    public function testRouteReplacementAttorneyDeleteTrustFallback()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/delete-trust'));
    
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/delete-trust'));
    
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/delete-trust'));
    }
    
    public function testRouteWhenReplacementAttorneyStepIn()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->getNearestAccessibleRoute('lpa/when-replacement-attorney-step-in'));
    }

    public function testRouteWhenReplacementAttorneyStepInFallback()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/when-replacement-attorney-step-in'));
        
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/when-replacement-attorney-step-in'));
        
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/when-replacement-attorney-step-in'));
        
        $this->setPrimaryAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/when-replacement-attorney-step-in'));
    }
    
    public function testRouteHowReplacementAttorneysMakeDecision()
    {
        $this->addReplacementAttorney(2);
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/how-replacement-attorneys-make-decision'));
        
        $this->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/how-replacement-attorneys-make-decision'));
    }

    public function testRouteHowReplacementAttorneysMakeDecisionFallback()
    {
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/how-replacement-attorneys-make-decision'));
        
        $this->setReplacementAttorneysStepInWhenFirstPrimaryUnableAct();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/how-replacement-attorneys-make-decision'));
        
        $this->addPrimaryAttorney(2);
        $this->addReplacementAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->getNearestAccessibleRoute('lpa/how-replacement-attorneys-make-decision'));
    }
    
    public function testRouteCertificateProvider1()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }
    
    public function testRouteCertificateProvider2()
    {
        $this->addPrimaryAttorney();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider3()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider4()
    {
        $this->addPrimaryAttorney();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider5()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider6()
    {
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->setReplacementAttorneysStepInWhenFirstPrimaryUnableAct();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->lpa->document->replacementAttorneyDecisions->when = ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST;
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->lpa->document->replacementAttorneyDecisions->when = ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS;
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProvider7()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysStepInWhenFirstPrimaryUnableAct();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    
        $this->setReplacementAttorneysStepInDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }
    
    public function testRouteCertificateProvider8()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->setReplacementAttorneysMakeDecisionDepends();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }
    
    public function testRouteCertificateProviderFallback1()
    {
        $this->addPrimaryAttorney();
        $this->addReplacementAttorney(2);
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProviderFallback2()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointly();
        $this->addReplacementAttorney(2);
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProviderFallback3()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney(2);
        $this->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProviderFallback4()
    {
        $this->addPrimaryAttorney(2);
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
        
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }

    public function testRouteCertificateProviderFallback5()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }
    
    public function testRouteCertificateProviderFallback6()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/certificate-provider'));
    }
    
    
    public function testRouteCertificateProviderAdd()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify/add', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/add'));
    }
    
    public function testRouteCertificateProviderAddFallback()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/add'));
    }
    
    public function testRouteCertificateProviderEdit()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/people-to-notify/edit', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/edit', 0));
    }
    
    public function testRouteCertificateProviderEditFallback()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/edit', 0));
    }
    
    public function testRouteCertificateProviderDelete()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/people-to-notify/delete', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/delete', 0));
    }
    
    public function testRouteCertificateProviderDeleteFallback()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/delete', 0));
    }
    
    public function testRoutePeopleToNotify()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify'));
    }
    
    public function testRoutePeopleToNotifyFallback()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify'));
    }
    
    public function testRoutePeopleToNotifyAdd()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify/add', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/add'));
    }
    
    public function testRoutePeopleToNotifyAddFallback()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/add'));
    }

    public function testRoutePeopleToNotifyEdit()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/people-to-notify/edit', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/edit', 0));
    }
    
    public function testRoutePeopleToNotifyEditFallback()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/edit', 0));
    }

    public function testRoutePeopleToNotifyDelete()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/people-to-notify/delete', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/delete', 0));
    }
    
    public function testRoutePeopleToNotifyDeleteFallback()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify', $this->checker->getNearestAccessibleRoute('lpa/people-to-notify/delete', 0));
    }
    
    public function testRouteInstructions()
    {
        $this->addCertificateProvider();
        $this->lpa->document->peopleToNotify = [];
        $this->lpa->metadata[Metadata::PEOPLE_TO_NOTIFY_CONFIRMED] = true;
        $this->assertEquals('lpa/instructions', $this->checker->getNearestAccessibleRoute('lpa/instructions'));
        
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/instructions', $this->checker->getNearestAccessibleRoute('lpa/instructions'));
    }
    
    public function testRouteInstructionsFallback()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/certificate-provider', $this->checker->getNearestAccessibleRoute('lpa/instructions'));
        
        $this->addCertificateProvider();
        $this->assertEquals('lpa/people-to-notify', $this->checker->getNearestAccessibleRoute('lpa/instructions'));
    }
    
    public function testRouteCreated()
    {
        $this->addPeopleToNotify();
        $this->setLpaInstructons();
        $this->assertEquals('lpa/created', $this->checker->getNearestAccessibleRoute('lpa/created'));
        
        $this->lpa->document->instruction = null;
        $this->lpa->document->preference = false;
        $this->assertEquals('lpa/created', $this->checker->getNearestAccessibleRoute('lpa/created'));
    }

    public function testRouteCreatedFallback()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/instructions', $this->checker->getNearestAccessibleRoute('lpa/created'));
    }
    
    public function testRouteDownload()
    {
        $this->setLpaCreated();
        $this->assertEquals('lpa/download', $this->checker->getNearestAccessibleRoute('lpa/download', 'lp1'));
    }
    
    public function testRouteRegister()
    {
        $this->setLpaCreated();
        $this->assertEquals('lpa/register', $this->checker->getNearestAccessibleRoute('lpa/register'));
    }
    
    public function testRouteApplicant()
    {
        $this->setLpaCreated();
        $this->assertEquals('lpa/applicant', $this->checker->getNearestAccessibleRoute('lpa/applicant'));
    }
    
    public function testRouteApplicantFallback()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/instructions', $this->checker->getNearestAccessibleRoute('lpa/applicant'));
        
        $this->setLpaInstructons();
        $this->assertEquals('lpa/created', $this->checker->getNearestAccessibleRoute('lpa/applicant'));
    }
    
    public function testRouteCorrespondent()
    {
        $this->setLpaApplicant();
        $this->assertEquals('lpa/correspondent', $this->checker->getNearestAccessibleRoute('lpa/correspondent'));
    }
    
    public function testRouteCorrespondentFallback()
    {
        $this->setLpaCreated();
        $this->assertEquals('lpa/applicant', $this->checker->getNearestAccessibleRoute('lpa/correspondent'));
    }

    public function testRouteCorrespondentEdit()
    {
        $this->setLpaCorrespondent();
        $this->assertEquals('lpa/correspondent/edit', $this->checker->getNearestAccessibleRoute('lpa/correspondent/edit'));
    }
    
    public function testRouteCorrespondentEditFallback()
    {
        $this->setLpaApplicant();
        $this->assertEquals('lpa/applicant', $this->checker->getNearestAccessibleRoute('lpa/correspondent/edit'));
    }
    
    public function testRouteWhoAreYou()
    {
        $this->setLpaCorrespondent();
        $this->assertEquals('lpa/who-are-you', $this->checker->getNearestAccessibleRoute('lpa/who-are-you'));
    }

    public function testRouteWhoAreYouFallback()
    {
        $this->setLpaApplicant();
        $this->assertEquals('lpa/correspondent', $this->checker->getNearestAccessibleRoute('lpa/who-are-you'));
    }
    
    public function testRouteFee()
    {
        $this->setWhoAreYouAnswered();
        $this->assertEquals('lpa/fee', $this->checker->getNearestAccessibleRoute('lpa/fee'));
    }
    
    public function testRouteFeeFallback()
    {
        $this->setLpaCorrespondent();
        $this->assertEquals('lpa/who-are-you', $this->checker->getNearestAccessibleRoute('lpa/fee'));
    }
    
    public function testRouteComplete()
    {
        $this->setWhoAreYouAnswered();
        $this->lpa->payment = new Payment();
        
        $this->lpa->payment->amount = null;
        $this->lpa->payment->reducedFeeUniversalCredit = true;
        $this->assertEquals('lpa/complete', $this->checker->getNearestAccessibleRoute('lpa/complete'));
        
        $this->lpa->payment->amount = 0.0;
        $this->lpa->payment->reducedFeeUniversalCredit = null;
        $this->assertEquals('lpa/complete', $this->checker->getNearestAccessibleRoute('lpa/complete'));
        
        $this->lpa->payment->amount = 100;
        $this->lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;
        $this->assertEquals('lpa/complete', $this->checker->getNearestAccessibleRoute('lpa/complete'));
        
        $this->lpa->payment->amount = 100;
        $this->lpa->payment->method = Payment::PAYMENT_TYPE_CARD;
        $this->lpa->payment->reference = "PAYMENT RECEIVED";
        $this->assertEquals('lpa/complete', $this->checker->getNearestAccessibleRoute('lpa/complete'));
        
    }
    
    public function testRouteCompleteFallback()
    {
        $this->setWhoAreYouAnswered();
        $this->lpa->payment = new Payment();
        $this->assertEquals('lpa/fee', $this->checker->getNearestAccessibleRoute('lpa/complete'));
        
        $this->lpa->payment->amount = 100;
        $this->lpa->payment->method = Payment::PAYMENT_TYPE_CARD;
        $this->assertEquals('lpa/fee', $this->checker->getNearestAccessibleRoute('lpa/complete'));
    }
    
    public function testRouteViewDocs()
    {
        $this->setPayment();
        $this->lpa->completedAt = new \DateTime();
        $this->assertEquals('lpa/view-docs', $this->checker->getNearestAccessibleRoute('lpa/view-docs'));
    }

    public function testRouteViewDocsFallback()
    {
        $this->setPayment();
        $this->assertEquals('lpa/fee', $this->checker->getNearestAccessibleRoute('lpa/view-docs'));
    }
    
    public function testReturnToFormType()
    {
        $this->setLpaTypePF();
        $this->assertEquals('lpa/form-type', $this->checker->backToForm());
    }
    
    public function testReturnToDonor()
    {
        $this->setLpaDonor();
        $this->assertEquals('lpa/donor', $this->checker->backToForm());
    }
    
    public function testReturnToLifeSustaining()
    {
        $this->setLifeSustaining();
        $this->assertEquals('lpa/life-sustaining', $this->checker->backToForm());
    }
    
    public function testReturnToWhenLpaStarts()
    {
        $this->setWhenLpaStarts();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->backToForm());
    }
    
    public function testReturnToPrimaryAttorney()
    {
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->backToForm());
    }

    public function testReturnToHowPrimaryAttorneysMakeDecision()
    {
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->backToForm());
    }

    public function testReturnToReplacementAttorney()
    {
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->backToForm());
    }

    public function testReturnToWhenReplacementAttorneyStepIn()
    {
        $this->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->backToForm());
    }

    public function testReturnToHowReplacementAttorneysMakeDecision()
    {
        $this->setReplacementAttorneysMakeDecisionJointly();
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->backToForm());
    }

    public function testReturnToCertificateProvider()
    {
        $this->addCertificateProvider();
        $this->assertEquals('lpa/certificate-provider', $this->checker->backToForm());
    }

    public function testReturnToPeopleToNotify()
    {
        $this->addPeopleToNotify();
        $this->assertEquals('lpa/people-to-notify', $this->checker->backToForm());
    }

    public function testReturnToInstructions()
    {
        $this->setLpaInstructons();
        $this->assertEquals('lpa/instructions', $this->checker->backToForm());
        
        $this->lpa->document->instruction = false;
        $this->assertEquals('lpa/instructions', $this->checker->backToForm());
    }

    public function testReturnToCreateLpa()
    {
        $this->setLpaCreated();
        $this->assertEquals('lpa/created', $this->checker->backToForm());
    }

    public function testReturnToApplicant()
    {
        $this->setLpaApplicant();
        $this->assertEquals('lpa/applicant', $this->checker->backToForm());
    }

    public function testReturnToCorrespondent()
    {
        $this->setLpaCorrespondent();
        $this->assertEquals('lpa/correspondent', $this->checker->backToForm());
    }

    public function testReturnToWhoAreYou()
    {
        $this->setWhoAreYouAnswered();
        $this->assertEquals('lpa/who-are-you', $this->checker->backToForm());
    }

    public function testReturnToFee()
    {
        $this->setPayment();
        $this->assertEquals('lpa/fee', $this->checker->backToForm());
    }

    public function testReturnToViewDocs()
    {
        $this->setPayment();
        $this->lpa->completedAt = new \DateTime();
        $this->assertEquals('lpa/view-docs', $this->checker->backToForm());
    }

    
############################## Private methods ###########################################################################

    private function setPayment()
    {
        $this->setWhoAreYouAnswered();
        $this->lpa->payment = new Payment();
        $this->lpa->payment->amount = 100;
        $this->lpa->payment->method = Payment::PAYMENT_TYPE_CARD;
        $this->lpa->payment->reference = "PAYMENT RECEIVED";
    }
    private function setWhoAreYouAnswered()
    {
        $this->setLpaCorrespondent();
        $this->lpa->whoAreYouAnswered = true;
    }
    
    private function setLpaApplicant()
    {
        $this->setLpaCreated();
        $this->lpa->document->whoIsRegistering = 'donor';
    }
    
    private function setLpaCorrespondent()
    {
        $this->setLpaApplicant();
        $this->lpa->document->correspondent = new Correspondence();
    }
    
    private function setLpaCreated()
    {
        $this->setLpaInstructons();
        $this->lpa->createdAt = new \DateTime();
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
    
    private function addReplacementAttorneyTrust()
    {
        if(count($this->lpa->document->primaryAttorneys) == 0) {
            $this->addPrimaryAttorney();
        }
        
        $this->lpa->document->replacementAttorneys[] = new TrustCorporation();
    }
    
    private function addReplacementAttorney($count=1)
    {
        if(count($this->lpa->document->primaryAttorneys) == 0) {
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

    private function addPrimaryAttorneyTrust()
    {
        $this->setWhenLpaStarts();
    
        $this->lpa->document->primaryAttorneys[] = new TrustCorporation();
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
        'createdAt'         => null,
        'updatedAt'         => new \DateTime(),
        'user'              => rand(10000, 9999999),
        'locked'            => false,
        'whoAreYouAnswered' => false,
        'document'          => new Document(),
        'metadata'          => [],
        ]);
        
        return $this->lpa;
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->FormFlowChecker = null;
    }
}

