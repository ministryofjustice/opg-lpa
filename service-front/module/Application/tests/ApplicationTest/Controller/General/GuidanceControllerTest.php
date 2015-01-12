<?php
namespace ApplicationTest\Controller\General;

use ApplicationTest\Controller\AbstractTest;

/**
 * GuidanceController test case.
 */
class GuidanceControllerTest extends AbstractTest
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
     * Tests GuidanceController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/guidance');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\General\Guidance');
        $this->assertControllerClass('GuidanceController');
        $this->assertMatchedRouteName('guidance');        
    }
}

