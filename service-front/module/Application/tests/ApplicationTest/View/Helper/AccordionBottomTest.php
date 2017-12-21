<?php
namespace ApplicationTest\View\Helper;
use Opg\Lpa\DataModel\Lpa\Lpa;
use PHPUnit\Framework\TestCase;

/**
 * AccordionBottom test case.
 */
class AccordionBottomTest extends TestCase
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

        $helperReturns = $this->getAccordion('lpa/form-type')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/donor',
                    ),
                    1 => array(
                        'routeName' => 'lpa/life-sustaining',
                    ),
                    2 => array(
                        'routeName' => 'lpa/primary-attorney',
                    ),
                    3 => array(
                        'routeName' => 'lpa/how-primary-attorneys-make-decision',
                    ),
                    4 => array(
                        'routeName' => 'lpa/replacement-attorney',
                    ),
                    5 => array(
                        'routeName' => 'lpa/when-replacement-attorney-step-in',
                    ),
                    6 => array(
                        'routeName' => 'lpa/certificate-provider',
                    ),
                    7 => array(
                        'routeName' => 'lpa/people-to-notify',
                    ),
                    8 => array(
                        'routeName' => 'lpa/instructions',
                    ),
                    9 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    10 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    11 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    12 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    13 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);
    }

    public function testDonor ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/donor')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/life-sustaining',
                    ),
                    1 => array(
                        'routeName' => 'lpa/primary-attorney',
                    ),
                    2 => array(
                        'routeName' => 'lpa/how-primary-attorneys-make-decision',
                    ),
                    3 => array(
                        'routeName' => 'lpa/replacement-attorney',
                    ),
                    4 => array(
                        'routeName' => 'lpa/when-replacement-attorney-step-in',
                    ),
                    5 => array(
                        'routeName' => 'lpa/certificate-provider',
                    ),
                    6 => array(
                        'routeName' => 'lpa/people-to-notify',
                    ),
                    7 => array(
                        'routeName' => 'lpa/instructions',
                    ),
                    8 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    9 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    10 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    11 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    12 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);
    }

    public function testLifeSustaining ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/life-sustaining')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/primary-attorney',
                    ),
                    1 => array(
                        'routeName' => 'lpa/how-primary-attorneys-make-decision',
                    ),
                    2 => array(
                        'routeName' => 'lpa/replacement-attorney',
                    ),
                    3 => array(
                        'routeName' => 'lpa/when-replacement-attorney-step-in',
                    ),
                    4 => array(
                        'routeName' => 'lpa/certificate-provider',
                    ),
                    5 => array(
                        'routeName' => 'lpa/people-to-notify',
                    ),
                    6 => array(
                        'routeName' => 'lpa/instructions',
                    ),
                    7 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    8 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    9 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    10 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    11 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);
    }

    public function testWhenLpaStarts ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/when-lpa-starts')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/primary-attorney',
                    ),
                    1 => array(
                        'routeName' => 'lpa/how-primary-attorneys-make-decision',
                    ),
                    2 => array(
                        'routeName' => 'lpa/replacement-attorney',
                    ),
                    3 => array(
                        'routeName' => 'lpa/when-replacement-attorney-step-in',
                    ),
                    4 => array(
                        'routeName' => 'lpa/certificate-provider',
                    ),
                    5 => array(
                        'routeName' => 'lpa/people-to-notify',
                    ),
                    6 => array(
                        'routeName' => 'lpa/instructions',
                    ),
                    7 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    8 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    9 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    10 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    11 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);
    }

    public function testPrimaryAttorney ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/primary-attorney')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/how-primary-attorneys-make-decision',
                    ),
                    1 => array(
                        'routeName' => 'lpa/replacement-attorney',
                    ),
                    2 => array(
                        'routeName' => 'lpa/when-replacement-attorney-step-in',
                    ),
                    3 => array(
                        'routeName' => 'lpa/certificate-provider',
                    ),
                    4 => array(
                        'routeName' => 'lpa/people-to-notify',
                    ),
                    5 => array(
                        'routeName' => 'lpa/instructions',
                    ),
                    6 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    7 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    8 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    9 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    10 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);
    }

    public function testPrimaryAttorneyDecision ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion(
            'lpa/how-primary-attorneys-make-decision')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/replacement-attorney',
                    ),
                    1 => array(
                        'routeName' => 'lpa/when-replacement-attorney-step-in',
                    ),
                    2 => array(
                        'routeName' => 'lpa/certificate-provider',
                    ),
                    3 => array(
                        'routeName' => 'lpa/people-to-notify',
                    ),
                    4 => array(
                        'routeName' => 'lpa/instructions',
                    ),
                    5 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    6 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    7 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    8 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    9 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);
    }

    public function testReplacementAttorney ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/when-replacement-attorney-step-in',
                    ),
                    1 => array(
                        'routeName' => 'lpa/certificate-provider',
                    ),
                    2 => array(
                        'routeName' => 'lpa/people-to-notify',
                    ),
                    3 => array(
                        'routeName' => 'lpa/instructions',
                    ),
                    4 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    5 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    6 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    7 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    8 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);

        $lpa->document->primaryAttorneys = [
                $lpa->document->primaryAttorneys[0]
        ];

        $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/how-replacement-attorneys-make-decision',
                    ),
                    1 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    2 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    3 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    4 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    5 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);
    }

    public function testReplacementAttorneyStepIn ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion(
            'lpa/when-replacement-attorney-step-in')->__invoke($lpa)->bottom();

        $this->assertEquals(
                array(
                    0 => array(
                        'routeName' => 'lpa/certificate-provider',
                    ),
                    1 => array(
                        'routeName' => 'lpa/people-to-notify',
                    ),
                    2 => array(
                        'routeName' => 'lpa/instructions',
                    ),
                    3 => array(
                        'routeName' => 'lpa/applicant',
                    ),
                    4 => array(
                        'routeName' => 'lpa/correspondent',
                    ),
                    5 => array(
                        'routeName' => 'lpa/who-are-you',
                    ),
                    6 => array(
                        'routeName' => 'lpa/repeat-application',
                    ),
                    7 => array(
                        'routeName' => 'lpa/fee-reduction',
                    )
                ), $helperReturns);
    }

    public function testCertificateProvider ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->bottom();

        $this->assertEquals(
            array(
                0 => array(
                    'routeName' => 'lpa/people-to-notify',
                ),
                1 => array(
                    'routeName' => 'lpa/instructions',
                ),
                2 => array(
                    'routeName' => 'lpa/applicant',
                ),
                3 => array(
                    'routeName' => 'lpa/correspondent',
                ),
                4 => array(
                    'routeName' => 'lpa/who-are-you',
                ),
                5 => array(
                    'routeName' => 'lpa/repeat-application',
                ),
                6 => array(
                    'routeName' => 'lpa/fee-reduction',
                )
                ), $helperReturns);

        $lpa->document->replacementAttorneyDecisions->when = 'first';

        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->bottom();

        $this->assertEquals(
            array(
                0 => array(
                    'routeName' => 'lpa/people-to-notify',
                ),
                1 => array(
                    'routeName' => 'lpa/instructions',
                ),
                2 => array(
                    'routeName' => 'lpa/applicant',
                ),
                3 => array(
                    'routeName' => 'lpa/correspondent',
                ),
                4 => array(
                    'routeName' => 'lpa/who-are-you',
                ),
                5 => array(
                    'routeName' => 'lpa/repeat-application',
                ),
                6 => array(
                    'routeName' => 'lpa/fee-reduction',
                )
                ), $helperReturns);

        $lpa->document->primaryAttorneyDecisions->how = 'depends';

        $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->bottom();

        $this->assertEquals(
            array(
                0 => array(
                    'routeName' => 'lpa/people-to-notify',
                ),
                1 => array(
                    'routeName' => 'lpa/instructions',
                ),
                2 => array(
                    'routeName' => 'lpa/applicant',
                ),
                3 => array(
                    'routeName' => 'lpa/correspondent',
                ),
                4 => array(
                    'routeName' => 'lpa/who-are-you',
                ),
                5 => array(
                    'routeName' => 'lpa/repeat-application',
                ),
                6 => array(
                    'routeName' => 'lpa/fee-reduction',
                )
                ), $helperReturns);
    }

    public function testPeopleToNotify ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/people-to-notify')->__invoke($lpa)->bottom();

        $this->assertEquals(
            array(
                0 => array(
                    'routeName' => 'lpa/instructions',
                ),
                1 => array(
                    'routeName' => 'lpa/applicant',
                ),
                2 => array(
                    'routeName' => 'lpa/correspondent',
                ),
                3 => array(
                    'routeName' => 'lpa/who-are-you',
                ),
                4 => array(
                    'routeName' => 'lpa/repeat-application',
                ),
                5 => array(
                    'routeName' => 'lpa/fee-reduction',
                )
                ), $helperReturns);
    }

    public function testInstructions ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $helperReturns = $this->getAccordion('lpa/instructions')->__invoke($lpa)->bottom();

        $this->assertEquals(array(
            0 => array(
                'routeName' => 'lpa/applicant',
            ),
            1 => array(
                'routeName' => 'lpa/correspondent',
            ),
            2 => array(
                'routeName' => 'lpa/who-are-you',
            ),
            3 => array(
                'routeName' => 'lpa/repeat-application',
            ),
            4 => array(
                'routeName' => 'lpa/fee-reduction',
            )
                ), $helperReturns);
    }

    public function testApplicant ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/applicant')->__invoke($lpa)->bottom();

        $this->assertEquals(
            array(
                0 => array(
                    'routeName' => 'lpa/correspondent',
                ),
                1 => array(
                    'routeName' => 'lpa/who-are-you',
                ),
                2 => array(
                    'routeName' => 'lpa/repeat-application',
                ),
                3 => array(
                    'routeName' => 'lpa/fee-reduction',
                )
                ), $helperReturns);
    }

    public function testCorrespondent ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/correspondent')->__invoke($lpa)->bottom();

        $this->assertEquals(
            array(
                0 => array(
                    'routeName' => 'lpa/who-are-you',
                ),
                1 => array(
                    'routeName' => 'lpa/repeat-application',
                ),
                2 => array(
                    'routeName' => 'lpa/fee-reduction',
                )
            ), $helperReturns);
    }

    public function testWhoAreYou ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/who-are-you')->__invoke($lpa)->bottom();

        $this->assertEquals(
            array(
                0 => array(
                    'routeName' => 'lpa/repeat-application',
                ),
                1 => array(
                    'routeName' => 'lpa/fee-reduction',
                )
            ), $helperReturns);
    }

    public function testRepeatApplication ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/repeat-application')->__invoke($lpa)->bottom();

        $this->assertEquals(
            array(
                0 => array(
                    'routeName' => 'lpa/fee-reduction',
                )
            ), $helperReturns);
    }

    public function testFeeReduction ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $helperReturns = $this->getAccordion('lpa/fee-reduction')->__invoke($lpa)->bottom();

        $this->assertEquals([], $helperReturns);
    }

    private function getAccordion ($routeName)
    {
        $accordion = $this->getMockBuilder('Application\View\Helper\Accordion')
            ->setMethods(array('getRouteName'))
            ->getMock();

        $accordion->expects($this->any())
            ->method('getRouteName')
            ->willReturn($routeName);

        return $accordion;
    }
}
