<?php
namespace ApplicationTest\View\Helper;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * AccordionTop test case.
 */
class AccordionTopTest extends \PHPUnit_Framework_TestCase
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
        
        $this->assertEmpty(
                $this->getAccordion('lpa/type')
                    ->__invoke($lpa));
    }

    public function testDonor ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/hw.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/donor')->__invoke($lpa);
        $this->assertEquals(
                [
                        0 => [
                                'name' => 'type.phtml',
                                'routeName' => 'lpa/form-type',
                                'lpaId' => $lpa->id,
                                'params' => [
                                        'idx' => 1,
                                        'values' => 'health-and-welfare'
                                ]
                        ]
                ], $helperReturns);
    }

    public function testLifeSustaining ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/hw.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/life-sustaining')->__invoke($lpa);
        $this->assertEquals(
                array(
                        0 => array(
                                'name' => 'type.phtml',
                                'routeName' => 'lpa/form-type',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 1,
                                        'values' => 'health-and-welfare'
                                )
                        ),
                        1 => array(
                                'name' => 'donor.phtml',
                                'routeName' => 'lpa/donor',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 2,
                                        'values' => 'Miss Tayla Travis'
                                )
                        )
                ), $helperReturns);
    }

    public function testWhenLpaStarts ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/when-lpa-starts')->__invoke($lpa);
        $this->assertEquals(
                array(
                        0 => array(
                                'name' => 'type.phtml',
                                'routeName' => 'lpa/form-type',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 1,
                                        'values' => 'property-and-financial'
                                )
                        ),
                        1 => array(
                                'name' => 'donor.phtml',
                                'routeName' => 'lpa/donor',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 2,
                                        'values' => 'Hon Ayden Armstrong'
                                )
                        )
                ), $helperReturns);
    }

    public function testPrimaryAttorney ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion('lpa/primary-attorney')->__invoke($lpa);
        $this->assertEquals(
                array(
                        0 => array(
                                'name' => 'type.phtml',
                                'routeName' => 'lpa/form-type',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 1,
                                        'values' => 'property-and-financial'
                                )
                        ),
                        1 => array(
                                'name' => 'donor.phtml',
                                'routeName' => 'lpa/donor',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 2,
                                        'values' => 'Hon Ayden Armstrong'
                                )
                        ),
                        2 => array(
                                'name' => 'when-lpa-starts.phtml',
                                'routeName' => 'lpa/when-lpa-starts',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 3,
                                        'values' => 'now'
                                )
                        )
                ), $helperReturns);
    }

    public function testPrimaryAttorneyDecision ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
        
        $helperReturns = $this->getAccordion(
                'lpa/how-primary-attorneys-make-decision')->__invoke($lpa);
        $this->assertEquals(
                array(
                        0 => array(
                                'name' => 'type.phtml',
                                'routeName' => 'lpa/form-type',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 1,
                                        'values' => 'property-and-financial'
                                )
                        ),
                        1 => array(
                                'name' => 'donor.phtml',
                                'routeName' => 'lpa/donor',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 2,
                                        'values' => 'Hon Ayden Armstrong'
                                )
                        ),
                        2 => array(
                                'name' => 'when-lpa-starts.phtml',
                                'routeName' => 'lpa/when-lpa-starts',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 3,
                                        'values' => 'now'
                                )
                        ),
                        3 => array(
                                'name' => 'primary-attorney.phtml',
                                'routeName' => 'lpa/primary-attorney',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 4,
                                        'values' => 'are Dr Lilly Simpson, Mr Marcel Tanner and Mrs Annabella Collier'
                                )
                        )
                ), $helperReturns);
    }

    public function testReplacementAttorney ()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/pf.json'));
        $lpa->id = 99999999;
    
        $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke(
                $lpa);
        $this->assertEquals(
                array(
                        0 => array(
                                'name' => 'type.phtml',
                                'routeName' => 'lpa/form-type',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 1,
                                        'values' => 'property-and-financial'
                                )
                        ),
                        1 => array(
                                'name' => 'donor.phtml',
                                'routeName' => 'lpa/donor',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 2,
                                        'values' => 'Hon Ayden Armstrong'
                                )
                        ),
                        2 => array(
                                'name' => 'when-lpa-starts.phtml',
                                'routeName' => 'lpa/when-lpa-starts',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 3,
                                        'values' => 'now'
                                )
                        ),
                        3 => array(
                                'name' => 'primary-attorney.phtml',
                                'routeName' => 'lpa/primary-attorney',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 4,
                                        'values' => 'are Dr Lilly Simpson, Mr Marcel Tanner and Mrs Annabella Collier'
                                )
                        ),
                        4 => array (
                                'name' => 'how-primary-attorneys-make-decision.phtml',
                                'routeName' => 'lpa/how-primary-attorneys-make-decision',
                                'lpaId' => $lpa->id,
                                'params' => 
                            array (
                                      'idx' => 5,
                                      'values' => 'jointly-attorney-severally',
                            ),
                        ),
                ), $helperReturns);
        
        $lpa->document->primaryAttorneys = [$lpa->document->primaryAttorneys[0]];
        
        $helperReturns = $this->getAccordion('lpa/replacement-attorney')->__invoke($lpa);
        $this->assertEquals(
                array(
                        0 => array(
                                'name' => 'type.phtml',
                                'routeName' => 'lpa/form-type',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 1,
                                        'values' => 'property-and-financial'
                                )
                        ),
                        1 => array(
                                'name' => 'donor.phtml',
                                'routeName' => 'lpa/donor',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 2,
                                        'values' => 'Hon Ayden Armstrong'
                                )
                        ),
                        2 => array(
                                'name' => 'when-lpa-starts.phtml',
                                'routeName' => 'lpa/when-lpa-starts',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 3,
                                        'values' => 'now'
                                )
                        ),
                        3 => array(
                                'name' => 'primary-attorney.phtml',
                                'routeName' => 'lpa/primary-attorney',
                                'lpaId' => $lpa->id,
                                'params' => array(
                                        'idx' => 4,
                                        'values' => 'is Dr Lilly Simpson'
                                )
                        ),
                ), $helperReturns);
        
    }
    
    private function getAccordion ($routeName)
    {
        $accordion = $this->getMockBuilder(
                'Application\View\Helper\AccordionTop')
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

