<?php
namespace ApplicationTest\View\Helper;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * AccordionBottom test case.
 */
class AccordionBottomTest extends \PHPUnit_Framework_TestCase
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

        echo json_encode($helperReturns);

        $this->assertEquals(
                array(
                        1 => array(
                                'name' => 'donor.phtml',
                                'routeName' => 'lpa/donor',
                                'lpaId' => 99999999,
                                'params' => array(
                                        'idx' => 2,
                                        'values' => 'Miss Tayla Travis'
                                )
                        ),
                        2 => array(
                                'name' => 'life-sustaining.phtml',
                                'routeName' => 'lpa/life-sustaining',
                                'lpaId' => 99999999,
                                'params' => array(
                                        'idx' => 3,
                                        'values' => true
                                )
                        ),
                        3 => array(
                                'name' => 'primary-attorney.phtml',
                                'routeName' => 'lpa/primary-attorney',
                                'lpaId' => 99999999,
                                'params' => array(
                                        'idx' => 4,
                                        'values' => 'The attorneys are Mr Bradley Adams, Mrs Jorja Sharp, Imran Landry, Dr Safaa Patrick and Thea Cantrell'
                                )
                        ),
                        4 => array(
                                'name' => 'how-primary-attorneys-make-decision.phtml',
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                                'lpaId' => 99999999,
                                'params' => array(
                                        'idx' => 5,
                                        'values' => 'depends'
                                )
                        ),
                        5 => array(
                                'name' => 'replacement-attorney.phtml',
                                'routeName' => 'lpa/replacement-attorney',
                                'lpaId' => 99999999,
                                'params' => array(
                                        'idx' => 6,
                                        'values' => 'The replacement attorneys are Mr Maisy Rivers, Billie Rasmussen and Miss Rory Boyle'
                                )
                        ),
                        6 => array(
                                'name' => 'certificate-provider.phtml',
                                'routeName' => 'lpa/certificate-provider',
                                'lpaId' => 99999999,
                                'params' => array(
                                        'idx' => 7,
                                        'values' => 'Ms Carter Delaney'
                                )
                        ),
                        7 => array(
                                'name' => 'people-to-notify.phtml',
                                'routeName' => 'lpa/people-to-notify',
                                'lpaId' => 99999999,
                                'params' => array(
                                        'idx' => 8,
                                        'values' => 'is Miss Elizabeth Stout'
                                )
                        ),
                        8 => array(
                                'name' => 'instructions.phtml',
                                'routeName' => 'lpa/instructions',
                                'lpaId' => 99999999,
                                'params' => array(
                                        'idx' => 9,
                                        'values' => 'Review'
                                )
                        )
                ), $helperReturns);
    }

    // public function testDonor ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion('lpa/donor')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     2 => array(
    //                             'name' => 'life-sustaining.phtml',
    //                             'routeName' => 'lpa/life-sustaining',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 3,
    //                                     'values' => true
    //                             )
    //                     ),
    //                     3 => array(
    //                             'name' => 'primary-attorney.phtml',
    //                             'routeName' => 'lpa/primary-attorney',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 4,
    //                                     'values' => 'The attorneys are Mr Bradley Adams, Mrs Jorja Sharp, Imran Landry, Dr Safaa Patrick and Thea Cantrell'
    //                             )
    //                     ),
    //                     4 => array(
    //                             'name' => 'how-primary-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-primary-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 5,
    //                                     'values' => 'depends'
    //                             )
    //                     ),
    //                     5 => array(
    //                             'name' => 'replacement-attorney.phtml',
    //                             'routeName' => 'lpa/replacement-attorney',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 6,
    //                                     'values' => 'The replacement attorneys are Mr Maisy Rivers, Billie Rasmussen and Miss Rory Boyle'
    //                             )
    //                     ),
    //                     6 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 7,
    //                                     'values' => 'Ms Carter Delaney'
    //                             )
    //                     ),
    //                     7 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => 'is Miss Elizabeth Stout'
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testLifeSustaining ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion('lpa/life-sustaining')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     3 => array(
    //                             'name' => 'primary-attorney.phtml',
    //                             'routeName' => 'lpa/primary-attorney',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 4,
    //                                     'values' => 'The attorneys are Mr Bradley Adams, Mrs Jorja Sharp, Imran Landry, Dr Safaa Patrick and Thea Cantrell'
    //                             )
    //                     ),
    //                     4 => array(
    //                             'name' => 'how-primary-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-primary-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 5,
    //                                     'values' => 'depends'
    //                             )
    //                     ),
    //                     5 => array(
    //                             'name' => 'replacement-attorney.phtml',
    //                             'routeName' => 'lpa/replacement-attorney',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 6,
    //                                     'values' => 'The replacement attorneys are Mr Maisy Rivers, Billie Rasmussen and Miss Rory Boyle'
    //                             )
    //                     ),
    //                     6 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 7,
    //                                     'values' => 'Ms Carter Delaney'
    //                             )
    //                     ),
    //                     7 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => 'is Miss Elizabeth Stout'
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testWhenLpaStarts ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion('lpa/when-lpa-starts')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     3 => array(
    //                             'name' => 'primary-attorney.phtml',
    //                             'routeName' => 'lpa/primary-attorney',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 4,
    //                                     'values' => 'The attorneys are Dr Lilly Simpson, Mr Marcel Tanner and Mrs Annabella Collier'
    //                             )
    //                     ),
    //                     4 => array(
    //                             'name' => 'how-primary-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-primary-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 5,
    //                                     'values' => 'jointly-attorney-severally'
    //                             )
    //                     ),
    //                     5 => array(
    //                             'name' => 'replacement-attorney.phtml',
    //                             'routeName' => 'lpa/replacement-attorney',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 6,
    //                                     'values' => 'The replacement attorneys are Ms Dennis Jackson, Mr Ethan Fulton and Mrs Aron Puckett'
    //                             )
    //                     ),
    //                     6 => array(
    //                             'name' => 'when-replacement-attorney-step-in.phtml',
    //                             'routeName' => 'lpa/when-replacement-attorney-step-in',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 7,
    //                                     'values' => 'last'
    //                             )
    //                     ),
    //                     7 => array(
    //                             'name' => 'how-replacement-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-replacement-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => 'depends'
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Dr Michaela Shepherd'
    //                             )
    //                     ),
    //                     9 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 10,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     10 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 11,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testPrimaryAttorney ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion('lpa/primary-attorney')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     4 => array(
    //                             'name' => 'how-primary-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-primary-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 5,
    //                                     'values' => 'jointly-attorney-severally'
    //                             )
    //                     ),
    //                     5 => array(
    //                             'name' => 'replacement-attorney.phtml',
    //                             'routeName' => 'lpa/replacement-attorney',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 6,
    //                                     'values' => 'The replacement attorneys are Ms Dennis Jackson, Mr Ethan Fulton and Mrs Aron Puckett'
    //                             )
    //                     ),
    //                     6 => array(
    //                             'name' => 'when-replacement-attorney-step-in.phtml',
    //                             'routeName' => 'lpa/when-replacement-attorney-step-in',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 7,
    //                                     'values' => 'last'
    //                             )
    //                     ),
    //                     7 => array(
    //                             'name' => 'how-replacement-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-replacement-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => 'depends'
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Dr Michaela Shepherd'
    //                             )
    //                     ),
    //                     9 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 10,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     10 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 11,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testPrimaryAttorneyDecision ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion(
    //         'lpa/how-primary-attorneys-make-decision')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     5 => array(
    //                             'name' => 'replacement-attorney.phtml',
    //                             'routeName' => 'lpa/replacement-attorney',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 6,
    //                                     'values' => 'The replacement attorneys are Ms Dennis Jackson, Mr Ethan Fulton and Mrs Aron Puckett'
    //                             )
    //                     ),
    //                     6 => array(
    //                             'name' => 'when-replacement-attorney-step-in.phtml',
    //                             'routeName' => 'lpa/when-replacement-attorney-step-in',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 7,
    //                                     'values' => 'last'
    //                             )
    //                     ),
    //                     7 => array(
    //                             'name' => 'how-replacement-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-replacement-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => 'depends'
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Dr Michaela Shepherd'
    //                             )
    //                     ),
    //                     9 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 10,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     10 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 11,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testReplacementAttorney ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     6 => array(
    //                             'name' => 'when-replacement-attorney-step-in.phtml',
    //                             'routeName' => 'lpa/when-replacement-attorney-step-in',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 7,
    //                                     'values' => 'last'
    //                             )
    //                     ),
    //                     7 => array(
    //                             'name' => 'how-replacement-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-replacement-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => 'depends'
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Dr Michaela Shepherd'
    //                             )
    //                     ),
    //                     9 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 10,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     10 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 11,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);

    //     $lpa->document->primaryAttorneys = [
    //             $lpa->document->primaryAttorneys[0]
    //     ];

    //     $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     5 => array(
    //                             'name' => 'how-replacement-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-replacement-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 6,
    //                                     'values' => 'depends'
    //                             )
    //                     ),
    //                     6 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 7,
    //                                     'values' => 'Dr Michaela Shepherd'
    //                             )
    //                     ),
    //                     7 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testReplacementAttorneyStepIn ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion(
    //         'lpa/when-replacement-attorney-step-in')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     7 => array(
    //                             'name' => 'how-replacement-attorneys-make-decision.phtml',
    //                             'routeName' => 'lpa/how-replacement-attorneys-make-decision',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => 'depends'
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Dr Michaela Shepherd'
    //                             )
    //                     ),
    //                     9 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 10,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     10 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 11,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testReplacementAttorneyMakeDecision ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion(
    //         'lpa/how-replacement-attorneys-make-decision')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     8 => array(
    //                             'name' => 'certificate-provider.phtml',
    //                             'routeName' => 'lpa/certificate-provider',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Dr Michaela Shepherd'
    //                             )
    //                     ),
    //                     9 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 10,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     10 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 11,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testCertificateProvider ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     9 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 10,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     10 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 11,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);

    //     $lpa->document->replacementAttorneyDecisions->when = 'first';

    //     $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     8 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     9 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 10,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);

    //     $lpa->document->primaryAttorneyDecisions->how = 'depends';

    //     $helperReturns = $this->getAccordion('lpa/certificate-provider')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     7 => array(
    //                             'name' => 'people-to-notify.phtml',
    //                             'routeName' => 'lpa/people-to-notify',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 8,
    //                                     'values' => ''
    //                             )
    //                     ),
    //                     8 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 9,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testPeopleToNotify ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion('lpa/people-to-notify')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     10 => array(
    //                             'name' => 'instructions.phtml',
    //                             'routeName' => 'lpa/instructions',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 11,
    //                                     'values' => 'Review'
    //                             )
    //                     )
    //             ), $helperReturns);
    // }

    // public function testInstructions ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;

    //     $helperReturns = $this->getAccordion('lpa/instructions')->__invoke($lpa)->bottom();

    //     $this->assertEquals([], $helperReturns);
    // }

    // public function testApplicant ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;
    //     $lpa->createdAt = new \DateTime();

    //     $helperReturns = $this->getAccordion('lpa/applicant')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     1 => array(
    //                             'name' => 'correspondent.phtml',
    //                             'routeName' => 'lpa/correspondent',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 2,
    //                                     'values' => 'Mrs Annabella Collier'
    //                             )
    //                     ),
    //                     2 => array(
    //                             'name' => 'who-are-you.phtml',
    //                             'routeName' => 'lpa/who-are-you',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 3,
    //                                     'values' => 'Who was using the LPA service answered'
    //                             )
    //                     ),
    //                     3 => array(
    //                             'name' => 'repeat-application.phtml',
    //                             'routeName' => 'lpa/repeat-application',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 4,
    //                                     'values' => "I’m making a repeat application"
    //                             )
    //                     ),
    //                     4 => array (
    //                             'name' => 'fee-reduction.phtml',
    //                             'routeName' => 'lpa/fee-reduction',
    //                             'lpaId' => 99999999,
    //                             'params' =>
    //                             array (
    //                                     'idx' => 5,
    //                                     'values' => 'I am applying for reduced fee',
    //                             ),
    //                     ),
    //                     5 => array (
    //                         'name' => 'payment.phtml',
    //                         'routeName' => 'lpa/payment',
    //                         'lpaId' => 99999999,
    //                         'params' =>
    //                             array (
    //                               'idx' => 6,
    //                               'values' => 'Application fee: £0.00 (Payment method: card)',
    //                             ),
    //                     ),
    //             ), $helperReturns);
    // }

    // public function testCorrespondent ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;
    //     $lpa->createdAt = new \DateTime();

    //     $helperReturns = $this->getAccordion('lpa/correspondent')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     2 => array(
    //                             'name' => 'who-are-you.phtml',
    //                             'routeName' => 'lpa/who-are-you',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 3,
    //                                     'values' => 'Who was using the LPA service answered'
    //                             )
    //                     ),
    //                     3 => array(
    //                             'name' => 'repeat-application.phtml',
    //                             'routeName' => 'lpa/repeat-application',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 4,
    //                                     'values' => 'I’m making a repeat application'
    //                             )
    //                     ),
    //                     4 => array (
    //                             'name' => 'fee-reduction.phtml',
    //                             'routeName' => 'lpa/fee-reduction',
    //                             'lpaId' => 99999999,
    //                             'params' =>
    //                             array (
    //                                     'idx' => 5,
    //                                     'values' => 'I am applying for reduced fee',
    //                             ),
    //                     ),
    //                     5 => array (
    //                         'name' => 'payment.phtml',
    //                         'routeName' => 'lpa/payment',
    //                         'lpaId' => 99999999,
    //                         'params' =>
    //                             array (
    //                               'idx' => 6,
    //                               'values' => 'Application fee: £0.00 (Payment method: card)',
    //                             ),
    //                     ),
    //             ), $helperReturns);
    // }

    // public function testWhoAreYou ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;
    //     $lpa->createdAt = new \DateTime();

    //     $helperReturns = $this->getAccordion('lpa/who-are-you')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     3 => array(
    //                             'name' => 'repeat-application.phtml',
    //                             'routeName' => 'lpa/repeat-application',
    //                             'lpaId' => 99999999,
    //                             'params' => array(
    //                                     'idx' => 4,
    //                                     'values' => 'I’m making a repeat application'
    //                             )
    //                     ),
    //                     4 => array (
    //                         'name' => 'fee-reduction.phtml',
    //                         'routeName' => 'lpa/fee-reduction',
    //                         'lpaId' => 99999999,
    //                         'params' =>
    //                             array (
    //                               'idx' => 5,
    //                               'values' => 'I am applying for reduced fee',
    //                             ),
    //                     ),
    //                     5 => array (
    //                         'name' => 'payment.phtml',
    //                         'routeName' => 'lpa/payment',
    //                         'lpaId' => 99999999,
    //                         'params' =>
    //                             array (
    //                               'idx' => 6,
    //                               'values' => 'Application fee: £0.00 (Payment method: card)',
    //                             ),
    //                     ),
    //             ), $helperReturns);
    // }

    // public function testRepeatApplication ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;
    //     $lpa->createdAt = new \DateTime();

    //     $helperReturns = $this->getAccordion('lpa/repeat-application')->__invoke($lpa)->bottom();

    //     $this->assertEquals(
    //             array(
    //                     4 => array (
    //                             'name' => 'fee-reduction.phtml',
    //                             'routeName' => 'lpa/fee-reduction',
    //                             'lpaId' => 99999999,
    //                             'params' =>
    //                             array (
    //                                     'idx' => 5,
    //                                     'values' => 'I am applying for reduced fee',
    //                             ),
    //                     ),
    //                     5 => array (
    //                         'name' => 'payment.phtml',
    //                         'routeName' => 'lpa/payment',
    //                         'lpaId' => 99999999,
    //                         'params' =>
    //                             array (
    //                               'idx' => 6,
    //                               'values' => 'Application fee: £0.00 (Payment method: card)',
    //                             ),
    //                     ),
    //             ), $helperReturns);
    // }

    // public function testFeeReduction ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;
    //     $lpa->createdAt = new \DateTime();

    //     $helperReturns = $this->getAccordion('lpa/fee-reduction')->__invoke($lpa)->bottom();

    //     $this->assertEquals([
    //             5 => array (
    //                     'name' => 'payment.phtml',
    //                     'routeName' => 'lpa/payment',
    //                     'lpaId' => 99999999,
    //                     'params' =>
    //                     array (
    //                             'idx' => 6,
    //                             'values' => 'Application fee: £0.00 (Payment method: card)',
    //                     ),
    //             ),
    //     ], $helperReturns);
    // }

    // public function testPayment ()
    // {
    //     $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
    //     $lpa->id = 99999999;
    //     $lpa->createdAt = new \DateTime();

    //     $helperReturns = $this->getAccordion('lpa/payment')->__invoke($lpa);

    //     $this->assertEquals([], $helperReturns);
    // }

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
