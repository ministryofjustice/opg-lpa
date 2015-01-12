<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * HowReplacementAttorneysMakeDecisionController test case.
 */
class HowReplacementAttorneysMakeDecisionControllerTest extends AbstractLpaTest
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
        parent::tearDown();
    }

    /**
     * Tests HowReplacementAttorneysMakeDecisionController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/how-replacement-attorneys-make-decision');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Lpa\HowReplacementAttorneysMakeDecision');
        $this->assertControllerClass('HowReplacementAttorneysMakeDecisionController');
        $this->assertMatchedRouteName('lpa/how-replacement-attorneys-make-decision');        
    }
}

