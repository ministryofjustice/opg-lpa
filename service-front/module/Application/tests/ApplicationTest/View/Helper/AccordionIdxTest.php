<?php
namespace ApplicationTest\View\Helper;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * AccordionIdx test case.
 */
class AccordionIdxTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->lpa = null;
        
        parent::tearDown();
    }

    /**
     * Test
     */
    public function testLpaType ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/hw.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/form-type')->__invoke($lpa);
        
        $this->assertEquals(1, $helperReturns);
    }

    public function testDonor ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/hw.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/donor')->__invoke($lpa);
        
        $this->assertEquals(2, $helperReturns);
    }

    public function testLifeSustaining ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/hw.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/life-sustaining')->__invoke(
                $lpa);
        
        $this->assertEquals(3, $helperReturns);
    }

    public function testWhenLpaStarts ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/when-lpa-starts')->__invoke(
                $lpa);
        
        $this->assertEquals(3, $helperReturns);
    }

    public function testPrimaryAttorney ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/primary-attorney')->__invoke(
                $lpa);
        
        $this->assertEquals(4, $helperReturns);
    }

    public function testPrimaryAttorneyDecision ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion(
                'lpa/how-primary-attorneys-make-decision')->__invoke($lpa);
        
        $this->assertEquals(5, $helperReturns);
    }

    public function testReplacementAttorney ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke(
                $lpa);
        
        $this->assertEquals(6, $helperReturns);
        
        $lpa->document->primaryAttorneys = [
                $lpa->document->primaryAttorneys[0]
        ];
        
        $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke(
                $lpa);
        
        $this->assertEquals(5, $helperReturns);
    }

    public function testReplacementAttorneyStepIn ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion(
                'lpa/when-replacement-attorney-step-in')->__invoke($lpa);
        
        $this->assertEquals(7, $helperReturns);
    }

    public function testReplacementAttorneyMakeDecision ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion(
                'lpa/how-replacement-attorneys-make-decision')->__invoke($lpa);
        
        $this->assertEquals(8, $helperReturns);
    }

    public function testCertificateProvider ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke(
                $lpa);
        
        $this->assertEquals(9, $helperReturns);
        
        $lpa->document->replacementAttorneyDecisions->when = 'first';
        
        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke(
                $lpa);
        
        $this->assertEquals(8, $helperReturns);
        
        $lpa->document->primaryAttorneyDecisions->how = 'depends';
        
        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke(
                $lpa);
        
        $this->assertEquals(7, $helperReturns);
    }

    public function testPeopleToNotify ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/people-to-notify')->__invoke(
                $lpa);
        
        $this->assertEquals(10, $helperReturns);
    }

    public function testInstructions ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/instructions')->__invoke($lpa);
        
        $this->assertEquals(11, $helperReturns);
    }

    public function testApplicant ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        $lpa->completedAt = new \DateTime();
        
        $helperReturns = $this->getAccordion('lpa/applicant')->__invoke($lpa);
        
        $this->assertEquals(1, $helperReturns);
    }

    public function testCorrespondent ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        $lpa->completedAt = new \DateTime();
        
        $helperReturns = $this->getAccordion('lpa/correspondent')->__invoke(
                $lpa);
        
        $this->assertEquals(2, $helperReturns);
    }

    public function testWhoAreYou ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        $lpa->completedAt = new \DateTime();
        
        $helperReturns = $this->getAccordion('lpa/who-are-you')->__invoke(
                $lpa);
        
        $this->assertEquals(3, $helperReturns);
    }

    public function testFee ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        $lpa->completedAt = new \DateTime();
        
        $helperReturns = $this->getAccordion('lpa/fee')->__invoke($lpa);
        
        $this->assertEquals(4, $helperReturns);
    }

    private function getAccordion ($routeName)
    {
        $accordion = $this->getMockBuilder(
                'Application\View\Helper\AccordionIdx')
            ->setMethods(array(
                'getRouteName'
        ))
            ->getMock();
        $accordion->expects($this->any())
            ->method('getRouteName')
            ->willReturn($routeName);
        return $accordion;
    }
}

