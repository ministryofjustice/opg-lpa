<?php
namespace ApplicationTest\View\Helper;
use Opg\Lpa\DataModel\Lpa\Lpa;
use PHPUnit\Framework\TestCase;

/**
 * AccordionTop test case.
 */
class AccordionTopTest extends TestCase
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
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->id = 99999999;

        $this->assertEmpty(
                $this->getAccordion('lpa/form-type')
                ->__invoke($lpa)->top());
    }

    public function testDonor ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/donor')->__invoke($lpa)->top();

        $this->assertEquals(
                [
                        0 => [
                                'routeName' => 'lpa/form-type',
                        ]
                ], $helperReturns);
    }

    public function testLifeSustaining ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/life-sustaining')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        )
                ), $helperReturns);
    }

    public function testWhenLpaStarts ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/when-lpa-starts')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        )
                ), $helperReturns);
    }

    public function testPrimaryAttorney ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/primary-attorney')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        )
                ), $helperReturns);
    }

    public function testPrimaryAttorneyDecision ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/how-primary-attorneys-make-decision')->__invoke($lpa)->top();

        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        )
                ), $helperReturns);
    }

    public function testReplacementAttorney ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke($lpa)->top();

        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        )
                ), $helperReturns);

        $lpa->document->primaryAttorneys = [
                $lpa->document->primaryAttorneys[0]
        ];

        $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke($lpa)->top();

        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        )
                ), $helperReturns);
    }

    public function testReplacementAttorneyStepIn ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion(
            'lpa/when-replacement-attorney-step-in')->__invoke($lpa)->top();

        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        )
                ), $helperReturns);
    }

    public function testReplacementAttorneyMakeDecision ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion(
            'lpa/how-replacement-attorneys-make-decision')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        )
                ), $helperReturns);
    }

    public function testCertificateProvider ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                ), $helperReturns);

        $lpa->document->replacementAttorneyDecisions->when = 'first';

        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->top();

        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        )
                ), $helperReturns);

        $lpa->document->primaryAttorneyDecisions->how = 'depends';

        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->top();

        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        )
                ), $helperReturns);
    }

    public function testPeopleToNotify ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/people-to-notify')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                        7 => array(
                                'routeName' => 'lpa/certificate-provider',
                        )
                ), $helperReturns);
    }

    public function testInstructions ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/instructions')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                        7 => array(
                                'routeName' => 'lpa/certificate-provider',
                        ),
                        8 => array(
                                'routeName' => 'lpa/people-to-notify',
                        )
                ), $helperReturns);
    }

    public function testApplicant ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/applicant')->__invoke($lpa)->top();
        $this->assertEquals(array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                        7 => array(
                                'routeName' => 'lpa/certificate-provider',
                        ),
                        8 => array(
                                'routeName' => 'lpa/people-to-notify',
                        ),
                        9 => array(
                            'routeName' => 'lpa/instructions',
                        ),
                        10 => array(
                            'routeName' => 'review-link',
                        )
                ), $helperReturns);
    }

    public function testCorrespondent ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/correspondent')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                        7 => array(
                                'routeName' => 'lpa/certificate-provider',
                        ),
                        8 => array(
                                'routeName' => 'lpa/people-to-notify',
                        ),
                        9 => array(
                            'routeName' => 'lpa/instructions',
                        ),
                        10 => array(
                            'routeName' => 'lpa/applicant',
                        ),
                        11 => array(
                            'routeName' => 'review-link',
                        )
                ), $helperReturns);
    }

    public function testWhoAreYou ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->completedAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/who-are-you')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                        7 => array(
                                'routeName' => 'lpa/certificate-provider',
                        ),
                        8 => array(
                                'routeName' => 'lpa/people-to-notify',
                        ),
                        9 => array(
                            'routeName' => 'lpa/instructions',
                        ),
                        10 => array(
                            'routeName' => 'lpa/applicant',
                        ),
                        11 => array(
                            'routeName' => 'lpa/correspondent',
                        ),
                        12 => array(
                            'routeName' => 'review-link',
                        )
                ), $helperReturns);
    }

    public function testRepeatApplication ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->completedAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/repeat-application')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                        7 => array(
                                'routeName' => 'lpa/certificate-provider',
                        ),
                        8 => array(
                                'routeName' => 'lpa/people-to-notify',
                        ),
                        9 => array(
                            'routeName' => 'lpa/instructions',
                        ),
                        10 => array(
                            'routeName' => 'lpa/applicant',
                        ),
                        11 => array(
                            'routeName' => 'lpa/correspondent',
                        ),
                        12 => array(
                            'routeName' => 'lpa/who-are-you',
                        ),
                        13 => array(
                            'routeName' => 'review-link',
                        )
                ), $helperReturns);
    }

    public function testFeeReduction ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->completedAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/fee-reduction')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                        7 => array(
                                'routeName' => 'lpa/certificate-provider',
                        ),
                        8 => array(
                                'routeName' => 'lpa/people-to-notify',
                        ),
                        9 => array(
                            'routeName' => 'lpa/instructions',
                        ),
                        10 => array(
                            'routeName' => 'lpa/applicant',
                        ),
                        11 => array(
                            'routeName' => 'lpa/correspondent',
                        ),
                        12 => array(
                            'routeName' => 'lpa/who-are-you',
                        ),
                        13 => array(
                            'routeName' => 'lpa/repeat-application',
                        ),
                        14 => array(
                            'routeName' => 'review-link',
                        )
                ), $helperReturns);
    }

    public function testPayment ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->completedAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/payment')->__invoke($lpa)->top();
        $this->assertEquals(
                array(
                        0 => array(
                                'routeName' => 'lpa/form-type',
                        ),
                        1 => array(
                                'routeName' => 'lpa/donor',
                        ),
                        2 => array(
                                'routeName' => 'lpa/when-lpa-starts',
                        ),
                        3 => array(
                                'routeName' => 'lpa/primary-attorney',
                        ),
                        4 => array(
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                        ),
                        5 => array(
                                'routeName' => 'lpa/replacement-attorney',
                        ),
                        6 => array(
                                'routeName' => 'lpa/when-replacement-attorney-step-in',
                        ),
                        7 => array(
                                'routeName' => 'lpa/certificate-provider',
                        ),
                        8 => array(
                                'routeName' => 'lpa/people-to-notify',
                        ),
                        9 => array(
                            'routeName' => 'lpa/instructions',
                        ),
                        10 => array(
                            'routeName' => 'lpa/applicant',
                        ),
                        11 => array(
                            'routeName' => 'lpa/correspondent',
                        ),
                        12 => array(
                            'routeName' => 'lpa/who-are-you',
                        ),
                        13 => array(
                            'routeName' => 'lpa/repeat-application',
                        ),
                        14 => array(
                            'routeName' => 'lpa/fee-reduction',
                        )
                ), $helperReturns);
    }

    private function getAccordion ($routeName)
    {
        $accordion = $this->getMockBuilder(
                'Application\View\Helper\Accordion')
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
