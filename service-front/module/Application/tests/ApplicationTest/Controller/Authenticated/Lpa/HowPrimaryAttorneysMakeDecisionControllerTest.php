<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * HowPrimaryAttorneysMakeDecisionController test case.
 */
class HowPrimaryAttorneysMakeDecisionControllerTest extends AbstractLpaTest
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
     * Tests HowPrimaryAttorneysMakeDecisionController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/how-primary-attorneys-make-decision');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecision');
        $this->assertControllerClass('HowPrimaryAttorneysMakeDecisionController');
        $this->assertMatchedRouteName('lpa/how-primary-attorneys-make-decision');        
    }
}

