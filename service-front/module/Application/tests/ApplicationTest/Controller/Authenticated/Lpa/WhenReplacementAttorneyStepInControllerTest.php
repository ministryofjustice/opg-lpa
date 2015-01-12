<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * WhenReplacementAttorneyStepInController test case.
 */
class WhenReplacementAttorneyStepInControllerTest extends AbstractLpaTest
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
     * Tests WhenReplacementAttorneyStepInController->indexAction()
     */
    public function testIndexAction ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/when-replacement-attorney-step-in');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepIn');
        $this->assertControllerClass('WhenReplacementAttorneyStepInController');
        $this->assertMatchedRouteName('lpa/when-replacement-attorney-step-in');        
    }
}

