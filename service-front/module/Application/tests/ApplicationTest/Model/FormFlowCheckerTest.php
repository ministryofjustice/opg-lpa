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
    
    private $formType;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        
        // set default form type
        $this->formType = Document::LPA_TYPE_PF;
        
        $this->lpa = $this->initLpa();
        
        $this->checker = new FormFlowChecker($this->lpa);
    }
    
    public function testFormTypeWithNewLpa()
    {
        $this->assertEquals('lpa/form-type', $this->checker->check('lpa/form-type'));
    }
    
    public function testFormTypeWithNoDocument()
    {
        $this->lpa->document = null;
        $this->assertEquals('user/dashboard', $this->checker->check('lpa/form-type'));
    }
    
    public function testRouteDonor()
    {
        $this->setLpaTypePF();
        $this->assertEquals('lpa/donor', $this->checker->check('lpa/donor'));
    }
    
    public function testRouteDonorWithNoLpaType()
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
        
        $this->addReplacementAttorney();
        $this->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->check('lpa/replacement-attorney/edit', 1));
    }
    
    
    
    public function testRouteWhenReplacementAttorneyStepIn()
    {
        
    }
    
    
############################## Private methods ###########################################################################    

    private function addReplacementAttorney($count=1)
    {
        $this->addPrimaryAttorney();
        for($i=0; $i<$count; $i++) {
            $this->lpa->document->replacementAttorneys[] = new Human();
        }
    }
    
    private function setPrimaryAttorneysMakeDecisionJointlySeverally()
    {
        $this->addPrimaryAttorney(2);
        $this->lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions( array(
            'how'   => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
            'when'  => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
            'canSustainLife' => null,
        ) );
    }

    private function setPrimaryAttorneysMakeDecisionJointly()
    {
        $this->addPrimaryAttorney(2);
        $this->lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions( array(
            'how'   => AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
            'when'  => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
            'canSustainLife' => null,
        ) );
    }

    private function setPrimaryAttorneysMakeDecisionDepends()
    {
        $this->addPrimaryAttorney(2);
        $this->lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions( array(
            'how'   => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
            'when'  => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
            'canSustainLife' => null,
        ) );
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
        $this->setLpaDonor();
        $this->lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions( array(
            'how'   => null,
            'when'  => null,
            'canSustainLife' => true,
        ) );
    }

    private function setWhenLpaStarts()
    {
        $this->setLpaDonor();
        $this->lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions( array(
        'how'   => null,
        'when'  => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
        'canSustainLife' => null,
        ) );
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

